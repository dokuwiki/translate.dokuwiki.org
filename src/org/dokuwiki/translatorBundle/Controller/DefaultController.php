<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller implements InitializableController {

    /**
     * @var RepositoryEntityRepository
     */
    private $repositoryRepository;

    /**
     * @var LanguageNameEntityRepository
     */
    private $languageRepository;

    public function initialize(Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $this->repositoryRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:RepositoryEntity');
        $this->languageRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:LanguageNameEntity');
    }

    public function indexAction() {
        $data['currentLanguage'] = $this->get('language_manager')->getLanguage($this->getRequest());
        $data['coreRepository'] = $this->repositoryRepository->getCoreRepositoryInformation($data['currentLanguage']);
        $data['repositories'] = $this->repositoryRepository->getPluginRepositoryInformation($data['currentLanguage']);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();

        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    public function showAction() {
        $data = array();
        $data['repository'] = $this->repositoryRepository->getCoreTranslation();

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }
}
