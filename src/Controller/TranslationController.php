<?php

namespace App\Controller;

use App\Entity\LanguageNameEntity;
use App\Services\GitHub\GitHubServiceException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use App\Repository\LanguageNameEntityRepository;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Services\Language\LanguageManager;
use App\Services\Language\LocalText;
use App\Services\Language\TranslationPreparer;
use App\Services\Language\UserTranslationValidator;
use App\Services\Language\UserTranslationValidatorFactory;
use App\Services\Repository\RepositoryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationController extends AbstractController {

    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;
    private RepositoryManager $repositoryManager;
    private LanguageManager $languageManager;
    private TranslationPreparer $translationPreparer;
    private RepositoryEntityRepository $repoRepository;

    public function __construct(RepositoryManager $repositoryManager, LanguageManager $languageManager,
                                TranslationPreparer $translationPreparer, EntityManagerInterface $entityManager) {
        $this->repositoryManager = $repositoryManager;
        $this->languageManager = $languageManager;
        $this->translationPreparer = $translationPreparer;
        $this->entityManager = $entityManager;
        $this->repoRepository = $entityManager->getRepository(RepositoryEntity::class);
    }

    /**
     * Try to save translated strings and redirect to thank page, home page or back to form
     *
     * @param Request $request
     * @param UserTranslationValidatorFactory $validatorFactory
     * @return Response
     *
     * @throws NoResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws GitHubServiceException
     */
    public function save(Request $request, UserTranslationValidatorFactory $validatorFactory): Response {
        if ($request->getMethod() !== 'POST') {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $action = $request->request->all('action');
        if (!isset($action['save'])) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data = [];
        $data['translation'] = $request->request->all('translation');
        $data['repositoryName'] = $request->request->get('repositoryName', '');
        $data['repositoryType'] = $request->request->get('repositoryType', '');
        if (
                empty($data['translation']) ||
                $data['repositoryName'] === '' ||
                $data['repositoryType'] === ''
            ) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data['name'] = $request->request->get('name', '');
        $data['email'] = $request->request->get('email', '');
        $language = $this->getLanguage($request);

        $repositoryEntity = $this->repoRepository->getRepository($data['repositoryType'], $data['repositoryName']);
        $repository = $this->repositoryManager->getRepository($repositoryEntity);
        $defaultTranslation = $repository->getLanguage('en');
        $previousTranslation = $repository->getLanguage($language);

        if($repositoryEntity->getEnglishReadonly() && $language == 'en') {
            $param = [];
            $param['type'] = $data['repositoryType'];
            $param['name'] = $data['repositoryName'];
            $param['englishReadonly'] = true;
            return $this->redirectToRoute('dokuwiki_translator_show_extension', $param);
        }

        $validator = $this->getUserTranslationValidator(
            $defaultTranslation, $previousTranslation, $data['translation'], $data['name'], $data['email'],
            $validatorFactory
        );
        $newTranslation = $validator->validate();
        $errors = $validator->getErrors();

        $userInput = [];
        $userInput['translation'] = $data['translation'];
        $userInput['errors'] = $errors;
        $userInput['author'] = $data['name'];
        $userInput['authorMail'] = $data['email'];
        if (!empty($errors)) {
             return $this->translate($request, $data['repositoryType'], $data['repositoryName'], $userInput);
        }

        // check only captcha if no other errors
        $captchaForm = $this->getCaptchaForm();
        $captchaForm->handleRequest($request);
        if (!($captchaForm->isSubmitted() && $captchaForm->isValid())) {
            return $this->translate($request, $data['repositoryType'], $data['repositoryName'], $userInput, $captchaForm);
        }

        $repository->addTranslationUpdate($newTranslation, $data['name'], $data['email'], $language);

        // forward to queue status
        $response = $this->redirectToRoute('dokuwiki_translate_thanks');
        $response->headers->setCookie(Cookie::create('author', $data['name']));
        $response->headers->setCookie(Cookie::create('authorMail', $data['email']));
        return $response;
    }

    /**
     * @param LocalText[] $defaultTranslation
     * @param LocalText[] $previousTranslation
     * @param array $userTranslation
     * @param string $author
     * @param string $authorEmail
     * @param UserTranslationValidatorFactory $validatorFactory
     * @return UserTranslationValidator
     */
    protected function getUserTranslationValidator(array $defaultTranslation, array $previousTranslation,
                                                   array $userTranslation, string $author, string $authorEmail,
                                                   UserTranslationValidatorFactory $validatorFactory): UserTranslationValidator
    {
        return $validatorFactory->getInstance($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail);
    }

    /**
     * Show form with translatable language strings for DokuWiki
     *
     * @param Request $request
     * @return RedirectResponse|Response
     *
     * @throws GitHubServiceException
     */
    public function translateCore(Request $request): Response {
        return $this->translate($request, RepositoryEntity::TYPE_CORE, 'dokuwiki');
    }

    /**
     * Show form with translatable language strings for extensions
     *
     * @param Request $request
     * @param string $type
     * @param string $name
     * @return RedirectResponse|Response
     *
     * @throws GitHubServiceException
     */
    public function translateExtension(Request $request, string $type, string $name): Response {
        return $this->translate($request, $type, $name);
    }

    /**
     * @param Request $request
     * @param string $type type of the translatable unit
     * @param string $name name of the extension
     * @param array $userInput input the user has already insert.
     *              This can contain the following keys:
     *                  - (array)  translation
     *                  - (array)  errors
     *                  - (string) author
     *                  - (string) authorMail
     * @param FormInterface|null $captchaForm
     * @return RedirectResponse|Response
     *
     * @throws GitHubServiceException
     */
    private function translate(Request $request, string $type, string $name, array $userInput = [], $captchaForm = null): Response {
        $data = [];
        $param = [];
        $language = $this->getLanguage($request);
        try {
            $repositoryEntity = $this->repoRepository->getRepository($type, $name);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        if ($repositoryEntity->getState() !== RepositoryEntity::STATE_ACTIVE) {
            $data['notActive'] = true;
            return $this->redirectToRoute('dokuwiki_translator_homepage', $data);
        }

        $data['repository'] = $repositoryEntity;
        $userTranslation = $userInput['translation'] ?? [];
        $data['translations'] = $this->prepareLanguages($language, $repositoryEntity, $userTranslation);
        $data['errors'] = $userInput['errors'] ?? [];
        $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');


        $cookies = $request->cookies;
        if (isset($userInput['author'])) {
            $data['author'] = $userInput['author'];
        } elseif ($cookies->has('author')) {
            $data['author'] = $cookies->get('author');
        } else {
            $data['author'] =  '';
        }

        if (isset($userInput['authorMail'])) {
            $data['authorMail'] = $userInput['authorMail'];
        } elseif ($cookies->has('authorMail')) {
            $data['authorMail'] = $cookies->get('authorMail');
        } else {
            $data['authorMail'] = '';
        }


        try {
            $data['targetLanguage'] = $this->getLanguageNameEntityRepository()->getLanguageByCode($language);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        if($repositoryEntity->getEnglishReadonly() && $data['targetLanguage']->getCode() == 'en') {
            $param['englishReadonly'] = true;

            if($type === RepositoryEntity::TYPE_CORE) {
                return $this->redirectToRoute('dokuwiki_translator_show', $param);
            } else {
                $param['type'] = $type;
                $param['name'] = $name;
                return $this->redirectToRoute('dokuwiki_translator_show_extension', $param);
            }
        }

        $data['openPR'] = $this->getOpenPRListInfo($repositoryEntity, $data['targetLanguage']);

        $captchaForm ??= $this->getCaptchaForm();
        $data['captcha'] = $captchaForm->createView();

        return $this->render('translate/translate.html.twig', $data);
    }

    private function getCaptchaForm(): FormInterface {
        return $this->createFormBuilder()
            ->add('captcha', CaptchaType::class)
            ->getForm();
    }

    private function prepareLanguages(string $language, RepositoryEntity $repositoryEntity, array $userTranslation): array {
        $repository = $this->repositoryManager->getRepository($repositoryEntity);

        $defaultTranslation = $repository->getLanguage('en');

        $targetTranslation = $userTranslation;
        if (empty($userTranslation)) {
            $targetTranslation = $repository->getLanguage($language);
        }

        return $this->translationPreparer->prepare($defaultTranslation, $targetTranslation);
    }

    /**
     * @return LanguageNameEntityRepository
     */
    private function getLanguageNameEntityRepository(): LanguageNameEntityRepository {
        return $this->entityManager->getRepository(LanguageNameEntity::class);
    }

    /**
     * Get information about the open pull requests of the given language
     *
     * @param RepositoryEntity $repositoryEntity
     * @param LanguageNameEntity $languageNameEntity
     * @return array with string listURL and int count
     *
     * @throws GitHubServiceException
     */
    private function getOpenPRListInfo(RepositoryEntity $repositoryEntity, LanguageNameEntity $languageNameEntity): array {
        $repository = $this->repositoryManager->getRepository($repositoryEntity);
        return $repository->getOpenPRListInfo($languageNameEntity);
    }

    /**
     * Show page to thank for the submitted translation
     *
     * @return Response
     */
    public function thanks(): Response
    {
        return $this->render('translate/thanks.html.twig');
    }

    private function getLanguage(Request $request): string {
        return $this->languageManager->getLanguage($request);
    }
}
