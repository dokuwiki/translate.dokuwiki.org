<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use org\dokuwiki\translatorBundle\Services\Language\TranslationPreparer;
use org\dokuwiki\translatorBundle\Services\Language\UserTranslationValidatorFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;

class TranslationController extends Controller implements InitializableController {

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function initialize(Request $request) {
        $this->entityManager = $this->getDoctrine()->getManager();
    }

    public function saveAction(Request $request) {
        if ($request->getMethod() !== 'POST') {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }



        $action = $request->request->get('action', array());
        if (!isset($action['save'])) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data = array();
        $data['translation'] = $request->request->get('translation', null);
        $data['repositoryName'] = $request->request->get('repositoryName', '');
        $data['repositoryType'] = $request->request->get('repositoryType', '');
        if (
                $data['translation'] === null ||
                $data['repositoryName'] === '' ||
                $data['repositoryType'] === ''
            ) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['name'] = $request->request->get('name', '');
        $data['email'] = $request->request->get('email', '');
        $language = $this->getLanguage();

        $repositoryEntity = $this->getRepositoryEntityRepository()->getRepository($data['repositoryType'], $data['repositoryName']);
        $repository = $this->getRepositoryManager()->getRepository($repositoryEntity);
        $defaultTranslation = $repository->getLanguage('en');
        $previousTranslation = $repository->getLanguage($language);

        $validator = $this->validateTranslation($defaultTranslation, $previousTranslation, $data['translation'], $data['name'], $data['email']);
        $newTranslation = $validator->validate();
        $errors = $validator->getErrors();
        if (!empty($errors)) {
            $userInput = array();
            $userInput['translation'] = $data['translation'];
            $userInput['errors'] = $errors;
            $userInput['author'] = $data['name'];
            $userInput['authorMail'] = $data['email'];
            return $this->translate($data['repositoryType'], $data['repositoryName'], $userInput);
        }

        $form = $this->getCaptchaForm();
        $form->bind($this->getRequest());
        if (!$form->isValid()) {
            $userInput = array();
            $userInput['translation'] = $data['translation'];
            $userInput['errors'] = $errors;
            $userInput['author'] = $data['name'];
            $userInput['authorMail'] = $data['email'];
            return $this->translate($data['repositoryType'], $data['repositoryName'], $userInput);
        }

        $repository->addTranslationUpdate($newTranslation, $data['name'], $data['email'], $language);

        // forward to queue status
        $response = $this->redirect($this->generateUrl('dokuwiki_translate_thanks'));
        $response->headers->setCookie(new Cookie('author', $data['name']));
        $response->headers->setCookie(new Cookie('authorMail', $data['email']));
        return $response;
    }

    protected function validateTranslation($defaultTranslation, $previousTranslation, array $userTranslation, $author, $authorEmail) {
        /** @var UserTranslationValidatorFactory $validatorFactory */
        $validatorFactory = $this->get('user_translation_validator_factory');
        $validator = $validatorFactory->getInstance($defaultTranslation, $previousTranslation,
                $userTranslation, $author, $authorEmail);
        return $validator;
    }

    public function translateCoreAction() {
        return $this->translate(RepositoryEntity::$TYPE_CORE, 'dokuwiki');
    }

    public function translatePluginAction($name) {
        return $this->translate(RepositoryEntity::$TYPE_PLUGIN, $name);
    }

    /**
     * @param string $type type of the translatable unit
     * @param string $name name of the plugin
     * @param array $userInput input the user has already insert.
     *              This can contain the following keys:
     *                  - (array)  translation
     *                  - (array)  errors
     *                  - (string) author
     *                  - (string) authorMail
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    private function translate($type, $name, array $userInput = array()) {
        $language = $this->getLanguage();
        $repositoryEntity = $this->getRepositoryEntityRepository()->getRepository($type, $name);

        if ($repositoryEntity->getState() !== RepositoryEntity::$STATE_ACTIVE) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['repository'] = $repositoryEntity;
        $userTranslation = isset($userInput['translation'])?$userInput['translation']:array();
        $data['translations'] = $this->prepareLanguages($language, $repositoryEntity, $userTranslation);
        $data['errors'] = isset($userInput['errors'])?$userInput['errors']:array();


        $cookies = $this->getRequest()->cookies;
        if (isset($userInput['author'])) $data['author'] = $userInput['author'];
        elseif ($cookies->has('author')) $data['author'] = $cookies->get('author');
        else $data['author'] =  '';

        if (isset($userInput['authorMail'])) $data['authorMail'] = $userInput['authorMail'];
        elseif ($cookies->has('authorMail')) $cookies->get('authorMail');
        else $data['authorMail'] = '';


        try {
            $data['targetLanguage'] = $this->getLanguageNameEntityRepository()->getLanguageByCode($language);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['captcha'] = $this->getCaptchaForm()->createView();

        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig', $data);
    }

    private function getCaptchaForm() {
        return $this->createFormBuilder()
            ->add('captcha', 'captcha')
            ->getForm();
    }

    /**
     * @return RepositoryManager
     */
    private function getRepositoryManager() {
        return $this->get('repository_manager');
    }

    private function prepareLanguages($language, $repositoryEntity, array $userTranslation) {
        $repositoryManager = $this->getRepositoryManager();
        $repository = $repositoryManager->getRepository($repositoryEntity);

        $defaultTranslation = $repository->getLanguage('en');

        $targetTranslation = $userTranslation;
        if (empty($userTranslation)) {
            $targetTranslation = $repository->getLanguage($language);
        }

        /** @var TranslationPreparer $translationPreparer */
        $translationPreparer = $this->get('translation_preparer');

        return $translationPreparer->prepare($defaultTranslation, $targetTranslation);
    }

    /**
     * @return LanguageNameEntityRepository
     */
    private function getLanguageNameEntityRepository() {
        return $this->entityManager->getRepository('dokuwikiTranslatorBundle:LanguageNameEntity');
    }

    public function thanksAction() {
        return $this->render('dokuwikiTranslatorBundle:Translate:thanks.html.twig');
    }

    private function getLanguage() {
        return $this->get('language_manager')->getLanguage($this->getRequest());
    }

    /**
     * @return RepositoryEntityRepository
     */
    private function getRepositoryEntityRepository() {
        return $this->entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');
    }
}
