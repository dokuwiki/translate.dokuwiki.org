<?php

namespace App\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use App\Repository\LanguageNameEntityRepository;
use App\Repository\RepositoryEntityRepository;
use App\Services\Language\LanguageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController {

    /**
     * Show front page
     * Language determined from url parameter, session or client info
     *
     * @param Request $request
     * @param LanguageManager $languageManager
     * @param RepositoryEntityRepository $repoEntityRepo
     * @param LanguageNameEntityRepository $langNameEntityRepo
     * @return Response
     *
     * @throws NonUniqueResultException
     */
    public function index(Request $request, LanguageManager $languageManager, RepositoryEntityRepository $repoEntityRepo,
                          LanguageNameEntityRepository $langNameEntityRepo) {
        $lang = $request->query->get('lang');

        if (!empty($lang)) {
            try {
                $langNameEntityRepo->getLanguageByCode($lang);
            } catch (NoResultException $e) {
                // just ignore unknown language codes because of spam.
                return $this->redirectToRoute('dokuwiki_translator_homepage');
            }
        }

        $data = [];
        $data['currentLanguage'] = $languageManager->getLanguage($request);
        $data['coreRepository'] = $repoEntityRepo->getCoreRepositoryInformation($data['currentLanguage']);
        $data['repositories'] = $repoEntityRepo->getExtensionRepositoryInformation($data['currentLanguage']);
        $data['languages'] = $langNameEntityRepo->getAvailableLanguages();
        $data['activated'] = $request->query->has('activated');
        $data['notActive'] = $request->query->has('notActive');
        $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');

        return $this->render('default/index.html.twig', $data);
    }

    /**
     * Show translation progress of DokuWiki
     *
     * @param Request $request
     * @param LanguageManager $languageManager
     * @param RepositoryEntityRepository $repoEntityRepo
     * @param LanguageNameEntityRepository $langNameEntityRepo
     * @return Response
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function show(Request $request, LanguageManager $languageManager, RepositoryEntityRepository $repoEntityRepo,
                         LanguageNameEntityRepository $langNameEntityRepo) {
        $data = [];
        $data['repository'] = $repoEntityRepo->getCoreTranslation();
        $data['currentLanguage'] = $languageManager->getLanguage($request);
        $data['languages'] = $langNameEntityRepo->getAvailableLanguages();
        $data['featureImportExport'] = $this->getParameter('app.featureImportExport');
        $data['featureAddTranslation'] = $this->getParameter('app.featureAddTranslation');
        $data['englishReadonly'] = $request->query->has('englishReadonly');
        $data['maxErrorCount'] = $this->getParameter('app.maxErrorCount');

        return $this->render('default/show.html.twig', $data);
    }
}
