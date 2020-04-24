<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

    /**
     * Show front page
     * Language determined from url parameter, session or client info
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function indexAction(Request $request) {
        $lang = $request->query->get('lang', null);

        if (!empty($lang)) {
            try {
                $this->languageRepository->getLanguageByCode($lang);
            } catch (NoResultException $e) {
                // just ignore unknown language codes because of spam.
                return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
            }
        }

        $data['currentLanguage'] = $this->get('language_manager')->getLanguage($request);
        $data['coreRepository'] = $this->repositoryRepository->getCoreRepositoryInformation($data['currentLanguage']);
        $data['repositories'] = $this->repositoryRepository->getExtensionRepositoryInformation($data['currentLanguage']);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();
        $data['activated'] = $request->query->has('activated');
        $data['notActive'] = $request->query->has('notActive');

        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    /**
     * Show translation progress of DokuWiki
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function showAction(Request $request) {
        $data = array();
        $data['repository'] = $this->repositoryRepository->getCoreTranslation();
        $data['currentLanguage'] = $this->get('language_manager')->getLanguage($request);
        $data['languages'] = $this->languageRepository->getAvailableLanguages();
        $data['featureImport'] = $this->container->getParameter('featureImport');
        $data['featureAddTranslationFromDetail'] = $this->container->getParameter('featureAddTranslationFromDetail');
        $data['englishReadonly'] = $request->query->has('englishReadonly');

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }
}
