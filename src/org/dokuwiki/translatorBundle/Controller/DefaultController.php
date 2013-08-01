<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntity;
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
        $lang = $this->getRequest()->query->get('lang', null);

        if ($lang !== null) {
            try {
                $this->languageRepository->getLanguageByCode($lang);
            } catch (NoResultException $e) {
                $result = new LanguageNameEntity();
                $result->setRtl(false);
                $result->setCode($lang);
                $result->setName($lang);
                $this->getDoctrine()->getManager()->persist($result);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        $data['currentLanguage'] = $this->get('language_manager')->getLanguage($this->getRequest());
        $data['coreRepository'] = $this->repositoryRepository->getCoreRepositoryInformation($data['currentLanguage']);
        $data['repositories'] = $this->repositoryRepository->getPluginRepositoryInformation($data['currentLanguage']);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();

        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    public function showAction() {
        $data = array();
        $data['repository'] = $this->repositoryRepository->getCoreTranslation();
        $data['featureImport'] = $this->container->getParameter('featureImport');
        $data['featureAddTranslationFromDetail'] = $this->container->getParameter('featureAddTranslationFromDetail');

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }
}
