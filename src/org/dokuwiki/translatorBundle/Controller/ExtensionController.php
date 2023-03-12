<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\EntityRepository\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\EntityRepository\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Form\RepositoryCreateType;
use org\dokuwiki\translatorBundle\Form\RepositoryRequestEditType;
use org\dokuwiki\translatorBundle\Services\DokuWikiRepositoryAPI\DokuWikiRepositoryAPI;
use org\dokuwiki\translatorBundle\Services\Language\LanguageManager;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtensionController extends Controller implements InitializableController {

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    /**
     * @var  LanguageNameEntityRepository
     */
    private $languageRepository;

//    public function __construct(RepositoryEntityRepository $repositoryRepository, LanguageNameEntityRepository $languageRepository) {
//        $this->repositoryRepository = $repositoryRepository;
//        $this->languageRepository = $languageRepository;
//    }
    public function initialize(Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $this->repositoryRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');
        $this->languageRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:LanguageNameEntity');
    }

    /**
     * Show form to add extension to translation tool, show on successful submit confirmation
     *
     * @param Request $request
     * @param string $type
     * @param DokuWikiRepositoryAPI $api
     * @return Response
     */
    public function indexAction(Request $request, $type, DokuWikiRepositoryAPI $api) {

        $data = array();

        $repository = new RepositoryEntity();
        $repository->setEmail('');
        $repository->setUrl('');
        $repository->setBranch('master');
        $repository->setType($type);

        $options['type'] = $type;
        $options['validation_groups'] = array('Default', $type);
        $options['action'] = RepositoryCreateType::ACTION_CREATE;
        $form = $this->createForm(RepositoryCreateType::class, $repository, $options);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addExtension($repository, $api);
            $data['repository'] = $repository;
            $data['maxErrorCount'] = $this->container->getParameter('maxErrorCount');
            return $this->render('dokuwikiTranslatorBundle:Extension:added.html.twig', $data);
        }

        $data['form'] = $form->createView();

        return $this->render('dokuwikiTranslatorBundle:Extension:add.html.twig', $data);
    }

    /**
     * Stores data of new extension
     *
     * @param RepositoryEntity $repository
     * @param DokuWikiRepositoryAPI $api
     */
    private function addExtension(RepositoryEntity $repository, DokuWikiRepositoryAPI $api) {
        $api->mergeExtensionInfo($repository);
        $repository->setLastUpdate(0);
        $repository->setState(RepositoryEntity::$STATE_WAITING_FOR_APPROVAL);
        $repository->setActivationKey($this->generateActivationKey($repository));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($repository);
        $entityManager->flush();

        // FIXME replace with mail service
        $data = array(
            'repository' => $repository,
        );
        $message = (new Swift_Message())
            ->setSubject('Registration')
            ->setTo($repository->getEmail())
            ->setFrom($this->container->getParameter('mailer_from'))
            ->setBody($this->renderView('dokuwikiTranslatorBundle:Mail:extensionAdded.txt.twig', $data));
        $this->get('mailer')->send($message);
    }

    private function generateActivationKey(RepositoryEntity $repository) {
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
     */
    public function activateAction($type, $name, $key) {

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndActivationKey($type, $name, $key);

            $repository->setState(RepositoryEntity::$STATE_INITIALIZING);
            $repository->setActivationKey('');
            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->merge($repository); //entity from getRepository is already managed?
            $entityManager->flush();

            $data['activated'] = true;

            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage', $data));
        } catch (NoResultException $ignored) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
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
    public function showAction(Request $request, $type, $name, LanguageManager $languageManager) {
        $data = array();

        try {
            $data['repository'] = $this->repositoryRepository->getExtensionTranslation($type, $name);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['currentLanguage'] = $languageManager->getLanguage($request);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();
        $data['featureImport'] = $this->container->getParameter('featureImport');
        $data['featureAddTranslationFromDetail'] = $this->container->getParameter('featureAddTranslationFromDetail');
        $data['englishReadonly'] = $request->query->has('englishReadonly');

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }

    /**
     * Show settings and request unique url for edit form of extension configuration
     *
     * @param Request $request
     * @param string $type
     * @param string $name
     * @return RedirectResponse|Response
     */
    public function settingsAction(Request $request, $type, $name) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepository($type, $name);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['urlSent'] = false;
        if($repository->getState() !== RepositoryEntity::$STATE_WAITING_FOR_APPROVAL) {
            $options['type'] = $type;
            $options['validation_groups'] = array('Default', $type);
            $form = $this->createForm(RepositoryRequestEditType::class, $repository, $options);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->createAndSentEditKey($repository);
                $data['urlSent'] = true;
            }
            $data['form'] = $form->createView();
        }
        $data['maxErrorCount'] = $this->container->getParameter('maxErrorCount');
        $data['repository'] = $repository;
        return $this->render('dokuwikiTranslatorBundle:Extension:settings.html.twig', $data);

    }

    /**
     * Store edit key and sent one-time edit url
     *
     * @param RepositoryEntity $repository
     */
    private function createAndSentEditKey(RepositoryEntity $repository) {
        $repository->setActivationKey($this->generateActivationKey($repository));
        $entityManager = $this->getDoctrine()->getManager();
//        $entityManager->merge($repository); //entity from getRepository is already managed?
        $entityManager->flush();

        // FIXME replace with mail service
        $data = array(
            'repository' => $repository,
        );
        $message = (new Swift_Message())
            ->setSubject('Edit ' . $repository->getType() . ' settings in DokuWiki Translation Tool')
            ->setTo($repository->getEmail())
            ->setFrom($this->container->getParameter('mailer_from'))
            ->setBody($this->renderView('dokuwikiTranslatorBundle:Mail:extensionEditUrl.txt.twig', $data));
        $this->get('mailer')->send($message);
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
     */
    public function editAction(Request $request, $type, $name, $key, RepositoryManager $repositoryManager) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndEditKey($type, $name, $key);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $originalValues = array(
            'url' => $repository->getUrl(),
            'branch' => $repository->getBranch()
        );

        $options['type'] = $type;
        $options['validation_groups'] = array('Default', $type);
        $options['action'] = RepositoryCreateType::ACTION_EDIT;
        $form = $this->createForm(RepositoryCreateType::class, $repository, $options);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateExtension($repository, $originalValues, $repositoryManager);

            $param['type'] = $type;
            $param['name'] = $name;
            return $this->redirect($this->generateUrl('dokuwiki_translator_extension_settings', $param));
        }

        $data['repository'] = $repository;
        $data['form'] = $form->createView();
        return $this->render('dokuwikiTranslatorBundle:Extension:edit.html.twig', $data);
    }

    /**
     * Stores updated extension data, and delete cloned repository if obsolete
     *
     * @param RepositoryEntity $repositoryEntity
     * @param array $originalValues
     * @param RepositoryManager $repositoryManager
     */
    private function updateExtension(RepositoryEntity $repositoryEntity, $originalValues, RepositoryManager $repositoryManager) {
        $repositoryEntity->setLastUpdate(0);
        $repositoryEntity->setActivationKey('');
        $entityManager = $this->getDoctrine()->getManager();
//        $entityManager->merge($repositoryEntity); //entity from getRepository is already managed?
        $entityManager->flush();

        $changed = $originalValues['branch'] !== $repositoryEntity->getBranch()
                || $originalValues['url'] !== $repositoryEntity->getUrl();

        if($changed) {
            $repository = $repositoryManager->getRepository($repositoryEntity);
            $repository->deleteCloneDirectory();
        }
    }
}
