<?php

namespace App\Controller;

use App\Services\Mail\MailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use App\Repository\LanguageNameEntityRepository;
use App\Entity\RepositoryEntity;
use App\Repository\RepositoryEntityRepository;
use App\Form\RepositoryCreateType;
use App\Form\RepositoryRequestEditType;
use App\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use App\Services\Language\LanguageManager;
use App\Services\Repository\RepositoryManager;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class ExtensionController extends AbstractController {

    private RepositoryEntityRepository $repositoryRepository;
    private LanguageNameEntityRepository $languageRepository;

    /**
     * @var EntityManager
     */
    private EntityManagerInterface $entityManager;

    public function __construct(RepositoryEntityRepository $repositoryRepository,
                                LanguageNameEntityRepository $languageRepository,
                                EntityManagerInterface $entityManager) {
        $this->repositoryRepository = $repositoryRepository;
        $this->languageRepository = $languageRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Show form to add extension to translation tool, show on successful submit confirmation
     *
     * @param Request $request
     * @param string $type
     * @param DokuWikiRepositoryAPI $api
     * @param MailService $mailer
     * @return Response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    public function index(Request $request, string $type, DokuWikiRepositoryAPI $api, MailService $mailer): Response
    {
        $data = [];

        $repository = new RepositoryEntity();
        $repository->setEmail('');
        $repository->setUrl('');
        $repository->setBranch('main');
        $repository->setType($type);
        $repository->setEnglishReadonly(true);

        $options = [];
        $options['type'] = $type;
        $options['validation_groups'] = ['Default', $type];
        $options['action'] = RepositoryCreateType::ACTION_CREATE;
        $form = $this->createForm(RepositoryCreateType::class, $repository, $options);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addExtension($repository, $api, $mailer);
            $data['repository'] = $repository;
            $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');
            return $this->render('extension/added.html.twig', $data);
        }

        $data['form'] = $form->createView();

        return $this->render('extension/add.html.twig', $data);
    }

    /**
     * Stores data of new extension
     *
     * @param RepositoryEntity $repository
     * @param DokuWikiRepositoryAPI $api
     * @param MailService $mailer
     *
     * @throws ORMException
     * @throws TransportExceptionInterface
     * @throws OptimisticLockException
     */
    private function addExtension(RepositoryEntity $repository, DokuWikiRepositoryAPI $api, MailService $mailer): void {
        $api->mergeExtensionInfo($repository);
        $repository->setLastUpdate(0);
        $repository->setState(RepositoryEntity::STATE_WAITING_FOR_APPROVAL);
        $repository->setActivationKey($this->generateActivationKey($repository));

        $this->entityManager->persist($repository);
        $this->entityManager->flush();

        $mailer->sendEmail(
            $repository->getEmail(),
            'Registration',
            'mail/extensionAdded.txt.twig',
            [
                'repository' => $repository
            ]
        );
    }

    private function generateActivationKey(RepositoryEntity $repository): string {
        return md5($repository->getType() . ':' . $repository->getName() . time());
    }

    /**
     * Handle activation link, redirects to homepage
     *
     * @param string $type
     * @param string $name
     * @param string $key
     * @return RedirectResponse
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function activate(string $type, string $name, string $key): RedirectResponse
    {
        $data = [];
        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndActivationKey($type, $name, $key);

            $repository->setState(RepositoryEntity::STATE_INITIALIZING);
            $repository->setActivationKey('');
            $this->entityManager->flush();

            $data['activated'] = true;

            return $this->redirectToRoute('dokuwiki_translator_homepage', $data);
        } catch (NoResultException $ignored) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }
    }

    /**
     * Show translation progress of requested extension
     *
     * @param Request $request
     * @param string $type
     * @param string $name
     * @param LanguageManager $languageManager
     * @return RedirectResponse|Response
     *
     * @throws NonUniqueResultException
     */
    public function show(Request $request, string $type, string $name, LanguageManager $languageManager): Response {
        $data = [];

        try {
            $data['repository'] = $this->repositoryRepository->getExtensionTranslation($type, $name);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data['currentLanguage'] = $languageManager->getLanguage($request);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();
        $data['featureImportExport'] = $this->getParameter('app.featureImportExport');
        $data['featureAddTranslation'] = $this->getParameter('app.featureAddTranslation');
        $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');
        $data['englishReadonly'] = $request->query->has('englishReadonly');

        return $this->render('default/show.html.twig', $data);
    }

    /**
     * Show settings and request unique url for edit form of extension configuration
     *
     * @param Request $request
     * @param string $type
     * @param string $name
     * @param MailerInterface $mailer
     * @return RedirectResponse|Response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    public function settings(Request $request, string $type, string $name, MailerInterface $mailer): Response {
        $data = [];

        try {
            $repository = $this->repositoryRepository->getRepository($type, $name);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data['urlSent'] = false;
        if($repository->getState() !== RepositoryEntity::STATE_WAITING_FOR_APPROVAL) {
            $options = [];
            $options['type'] = $type;
            $options['validation_groups'] = ['Default', $type];
            $form = $this->createForm(RepositoryRequestEditType::class, $repository, $options);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->createAndSentEditKey($repository, $mailer);
                $data['urlSent'] = true;
            }
            $data['form'] = $form->createView();
        }
        $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');
        $data['repository'] = $repository;
        return $this->render('extension/settings.html.twig', $data);

    }

    /**
     * Store edit key and sent one-time edit url
     *
     * @param RepositoryEntity $repository
     * @param MailerInterface $mailer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     */
    private function createAndSentEditKey(RepositoryEntity $repository, MailerInterface $mailer): void {
        $repository->setActivationKey($this->generateActivationKey($repository));
        $this->entityManager->flush();

        $email = (new TemplatedEmail())
            ->subject('Edit ' . $repository->getType() . ' settings in DokuWiki Translation Tool')
            ->to($repository->getEmail())
            ->textTemplate('mail/extensionEditUrl.txt.twig')
            ->context([
                'repository' => $repository
            ]);
        $mailer->send($email);
    }

    /**
     * Edit form of extension configuration
     *
     * @param Request $request
     * @param string $type
     * @param string $name
     * @param string $key
     * @param RepositoryManager $repositoryManager
     * @return RedirectResponse|Response
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function edit(Request $request, string $type, string $name, string $key, RepositoryManager $repositoryManager): Response
    {
        $options = [];
        $param = [];
        $data = [];

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndEditKey($type, $name, $key);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $originalValues = [
            'url' => $repository->getUrl(),
            'branch' => $repository->getBranch()
        ];

        $options['type'] = $type;
        $options['validation_groups'] = ['Default', $type];
        $options['action'] = RepositoryCreateType::ACTION_EDIT;
        $form = $this->createForm(RepositoryCreateType::class, $repository, $options);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateExtension($repository, $originalValues, $repositoryManager);

            $param['type'] = $type;
            $param['name'] = $name;
            return $this->redirectToRoute('dokuwiki_translator_extension_settings', $param);
        }

        $data['repository'] = $repository;
        $data['form'] = $form->createView();
        return $this->render('extension/edit.html.twig', $data);
    }

    /**
     * Stores updated extension data, and delete cloned repository if obsolete
     *
     * @param RepositoryEntity $repositoryEntity
     * @param array $originalValues
     * @param RepositoryManager $repositoryManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateExtension(RepositoryEntity $repositoryEntity, array $originalValues, RepositoryManager $repositoryManager): void {
        $repositoryEntity->setLastUpdate(0);
        $repositoryEntity->setErrorCount(0); //save of edit form resets error count on purpose
        $repositoryEntity->setActivationKey('');
        $this->entityManager->flush();

        $changed = $originalValues['branch'] !== $repositoryEntity->getBranch()
                || $originalValues['url'] !== $repositoryEntity->getUrl();

        if($changed) {
            $repository = $repositoryManager->getRepository($repositoryEntity);
            $repository->deleteCloneDirectory();
        }
    }
}
