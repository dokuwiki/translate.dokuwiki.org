<?php

namespace App\Controller;

use App\Services\Mail\MailService;
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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class ExtensionController extends AbstractController {

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    /**
     * @var  LanguageNameEntityRepository
     */
    private $languageRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(RepositoryEntityRepository $repositoryRepository, LanguageNameEntityRepository $languageRepository, EntityManagerInterface $entityManager) {
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
     * @throws TransportExceptionInterface
     */
    public function index(Request $request, $type, DokuWikiRepositoryAPI $api, MailService $mailer) {

        $data = array();

        $repository = new RepositoryEntity();
        $repository->setEmail('');
        $repository->setUrl('');
        $repository->setBranch('master');
        $repository->setType($type);
        $repository->setEnglishReadonly(true);

        $options['type'] = $type;
        $options['validation_groups'] = array('Default', $type);
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
     * @throws TransportExceptionInterface
     */
    private function addExtension(RepositoryEntity $repository, DokuWikiRepositoryAPI $api, MailService $mailer) {
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
     * @throws ORMException
     */
    public function activate($type, $name, $key) {

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
    public function show(Request $request, $type, $name, LanguageManager $languageManager) {
        $data = array();

        try {
            $data['repository'] = $this->repositoryRepository->getExtensionTranslation($type, $name);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data['currentLanguage'] = $languageManager->getLanguage($request);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();
        $data['featureImportExport'] = $this->getParameter('app.featureImportExport');
        $data['featureAddTranslation'] = $this->getParameter('app.featureAddTranslation');
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
     * @throws TransportExceptionInterface
     */
    public function settings(Request $request, $type, $name, MailerInterface $mailer) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepository($type, $name);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
        }

        $data['urlSent'] = false;
        if($repository->getState() !== RepositoryEntity::STATE_WAITING_FOR_APPROVAL) {
            $options['type'] = $type;
            $options['validation_groups'] = array('Default', $type);
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
     * @throws TransportExceptionInterface
     */
    private function createAndSentEditKey(RepositoryEntity $repository, MailerInterface $mailer) {
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
     */
    public function edit(Request $request, $type, $name, $key, RepositoryManager $repositoryManager) {
        $data = array();

        try {
            $repository = $this->repositoryRepository->getRepositoryByNameAndEditKey($type, $name, $key);
        } catch (NoResultException $e) {
            return $this->redirectToRoute('dokuwiki_translator_homepage');
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
     */
    private function updateExtension(RepositoryEntity $repositoryEntity, $originalValues, RepositoryManager $repositoryManager) {
        $repositoryEntity->setLastUpdate(0);
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
