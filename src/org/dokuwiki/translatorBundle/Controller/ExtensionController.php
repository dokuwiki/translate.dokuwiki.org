<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Form\RepositoryEditType;
use org\dokuwiki\translatorBundle\Services\Mail\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Form\RepositoryCreateType;
use org\dokuwiki\translatorBundle\Form\RepositoryRequestEditType;

class ExtensionController extends Controller implements InitializableController {

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    public function initialize(Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $this->repositoryRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');
    }

    /**
     * Show form to add extension to translation tool, show on successful submit confirmation
     *
     * @param string $type
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\AlreadyBoundException
     */
    public function indexAction($type, Request $request) {

        $data = array();

        $repository = new RepositoryEntity();
        $repository->setEmail('');
        $repository->setUrl('');
        $repository->setBranch('master');
        $repository->setType($type);

        $options['type'] = $type;
        $options['validation_groups'] = array('Default', $type);
        $options['action'] = RepositoryCreateType::ACTION_CREATE;
        $form = $this->createForm(new RepositoryCreateType(), $repository, $options);

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->addExtension($repository);
                $data['repository'] = $repository;
                return $this->render('dokuwikiTranslatorBundle:Extension:added.html.twig', $data);
            }
        }

        $data['form'] = $form->createView();

        return $this->render('dokuwikiTranslatorBundle:Extension:add.html.twig', $data);
    }

    private function addExtension(RepositoryEntity $repository) {
        $api = $this->get('doku_wiki_repository_api');

        $api->mergeExtensionInfo($repository);
        $repository->setLastUpdate(0);
        $repository->setState(RepositoryEntity::$STATE_WAITING_FOR_APPROVAL);
        $repository->setActivationKey($this->generateActivationKey($repository));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($repository);
        $entityManager->flush();

        // FIXME replace with mail service
        $message = \Swift_Message::newInstance();
        $message->setSubject('Registration');
        $message->setTo($repository->getEmail());
        $message->setFrom($this->container->getParameter('mailer_from'));
        $data = array(
            'repository' => $repository,
        );
        $message->setBody($this->renderView('dokuwikiTranslatorBundle:Mail:extensionAdded.txt.twig', $data));
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function activateAction($type, $name, $key) {

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndActivationKey($type, $name, $key);

            $repository->setState(RepositoryEntity::$STATE_INITIALIZING);
            $repository->setActivationKey('');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->merge($repository);
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
     * @param string $type
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function showAction($type, $name) {
        $data = array();

        try {
            $data['repository'] = $this->repositoryRepository->getExtensionTranslation($type, $name);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['featureImport'] = $this->container->getParameter('featureImport');
        $data['featureAddTranslationFromDetail'] = $this->container->getParameter('featureAddTranslationFromDetail');
        $data['englishreadonly'] = $this->getRequest()->query->has('englishreadonly');

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }

    /**
     * Show settings and request unique url for edit form of extension configuration
     *
     * @param string $type
     * @param string $name
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction($type, $name, Request $request) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepository($type, $name);  // get not waiting repos TODO only for form
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $data['urlsent'] = false;
        if($repository->getState() !== RepositoryEntity::$STATE_WAITING_FOR_APPROVAL) {
            $options['type'] = $type;
            $options['validation_groups'] = array('Default', $type);
            $form = $this->createForm(new RepositoryRequestEditType(), $repository, $options);

            if ($request->isMethod('POST')) {
                $form->bind($request);
                if ($form->isValid()) {
                    $this->createAndSentEditKey($repository);
                    $data['urlsent'] = true;
                }
            }
            $data['form'] = $form->createView();
        }

        $data['repository'] = $repository;
        return $this->render('dokuwikiTranslatorBundle:Extension:settings.html.twig', $data);

    }

    private function createAndSentEditKey(RepositoryEntity $repository) {
        $repository->setActivationKey($this->generateActivationKey($repository));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($repository);
        $entityManager->flush();

        // FIXME replace with mail service
        $message = \Swift_Message::newInstance();
        $message->setSubject('Edit ' . $repository->getType() . ' settings in DokuWiki Translation Tool');
        $message->setTo($repository->getEmail());
        $message->setFrom($this->container->getParameter('mailer_from'));
        $data = array(
            'repository' => $repository,
        );
        $message->setBody($this->renderView('dokuwikiTranslatorBundle:Mail:extensionEditUrl.txt.twig', $data));
        $this->get('mailer')->send($message);
    }

    /**
     * Edit form of extension configuration
     *
     * @param string $type
     * @param string $name
     * @param string $key
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($type, $name, $key, Request $request) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndEditKey($type, $name, $key);
        } catch (NoResultException $e) {
            return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
        }

        $originalvalues = array(
            'url' => $repository->getUrl(),
            'branch' => $repository->getBranch()
        );

        $options['type'] = $type;
        $options['validation_groups'] = array('Default', $type);
        $options['action'] = RepositoryCreateType::ACTION_EDIT;
        $form = $this->createForm(new RepositoryCreateType(), $repository, $options);

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                $this->updateExtension($repository, $originalvalues);

                $param['type'] = $type;
                $param['name'] = $name;
                return $this->redirect($this->generateUrl('dokuwiki_translator_extension_settings', $param));
            }
        }

        $data['repository'] = $repository;
        $data['form'] = $form->createView();
        return $this->render('dokuwikiTranslatorBundle:Extension:edit.html.twig', $data);
    }

    private function updateExtension(RepositoryEntity $repositoryEntity, $originalvalues) {
        $repositoryEntity->setLastUpdate(0);
        $repositoryEntity->setActivationKey('');
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($repositoryEntity);
        $entityManager->flush();

        $changed = $originalvalues['branch'] !== $repositoryEntity->getBranch()
                || $originalvalues['url'] !== $repositoryEntity->getUrl();

        if($changed) {
            $repository = $this->get('repository_manager')->getRepository($repositoryEntity);
            $repository->deleteCloneDirectory();
        }
    }
}
