<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntityRepository;
use org\dokuwiki\translatorBundle\Services\Language\LocalText;
use org\dokuwiki\translatorBundle\Services\Repository\Repository;
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
        $newTranslation = $this->validateTranslation($repository, $data['translation'], $language, $data['name'], $data['email']);

        $jobId = $repository->addTranslationUpdate($newTranslation, $data['name'], $data['email'], $language);

        // forward to queue status
        return $this->redirect($this->generateUrl('dokuwiki_translate_thanks'));
    }

    private function validateTranslation(Repository $repository, array $userTranslation, $language, $author, $authorEmail) {

        $newTranslation = array();
        $defaultTranslation = $repository->getLanguage('en');
        $previusTranslation = $repository->getLanguage($language);

        foreach ($defaultTranslation as $path => $translation) {
            if (!isset($userTranslation[$path])) {
                continue;
            }

            if ($translation->getType() !== LocalText::$TYPE_ARRAY) {
                $newTranslation[$path] = new LocalText($userTranslation[$path], LocalText::$TYPE_MARKUP);
                continue;
            }

            $newTranslationArray = array();
            $translationArray = $translation->getContent();
            foreach ($translationArray as $key => $text) {
                if (!isset($userTranslation[$path][$key])) {
                    continue;
                }

                if ($key !== 'js') {
                    $newTranslationArray[$key] = $userTranslation[$path][$key];
                    continue;
                }

                $newTranslationArray[$key] = array();
                foreach ($text as $jsKey => $jsVal) {
                    if (!isset($userTranslation[$path][$key][$jsKey])) {
                        continue;
                    }
                    $newTranslationArray[$key] = $userTranslation[$path][$key][$jsKey];
                    continue;
                }
            }
            $authors = array();
            if (!empty($author)) {
                if (isset($previusTranslation[$path])) {
                    $authors = $previusTranslation[$path]->getAuthors();
                }
                $authors[$author] = $authorEmail;
            }
            $newTranslation[$path] = new LocalText($newTranslationArray, LocalText::$TYPE_ARRAY, $authors);
        }

        return $newTranslation;

    }

    public function translateCoreAction() {
        return $this->translate(RepositoryEntity::$TYPE_CORE, 'dokuwiki');
    }

    public function translatePluginAction($name) {
        return $this->translate(RepositoryEntity::$TYPE_PLUGIN, $name);
    }

    private function translate($type, $name) {
        $language = $this->getLanguage();
        $repositoryEntity = $this->getRepositoryEntityRepository()->getRepository($type, $name);

        $data['repository'] = $repositoryEntity;
        $data['translations'] = $this->prepareLanguages($language, $repositoryEntity);
        $data['targetLanguageName'] = $this->getLanguageNameEntityRepository()->getLanguageNameByCode($language);

        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig',
                             $data);
    }


    /**
     * @return RepositoryManager
     */
    private function getRepositoryManager() {
        return $this->get('repository_manager');
    }

    private function prepareLanguages($language, $repositoryEntity) {
        $repositoryManager = $this->getRepositoryManager();
        $repository = $repositoryManager->getRepository($repositoryEntity);

        $defaultTranslation = $repository->getLanguage('en');
        $targetTranslation = $repository->getLanguage($language);

        $missingTranslations = array();
        $availableTranslations = array();


        foreach ($defaultTranslation as $path => $translation) {
            if ($translation->getType() !== LocalText::$TYPE_ARRAY) {
                $entry = $this->createEntry($defaultTranslation, $targetTranslation, $path);

                if (empty($entry['target'])) {
                    $missingTranslations[] = $entry;
                    continue;
                }
                $availableTranslations[] = $entry;
                continue;
            }
            $translationArray = $translation->getContent();
            foreach ($translationArray as $key => $text) {
                if ($key !== 'js') {
                    $entry = $this->createEntry($defaultTranslation, $targetTranslation, $path, $key);

                    if (empty($entry['target'])) {
                        $missingTranslations[] = $entry;
                        continue;
                    }
                    $availableTranslations[] = $entry;
                    continue;
                }
                foreach ($text as $jsKey => $jsVal) {
                    $entry = $this->createEntry($defaultTranslation, $targetTranslation, $path, $key, $jsKey);

                    if (empty($entry['target'])) {
                        $missingTranslations[] = $entry;
                        continue;
                    }
                    $availableTranslations[] = $entry;
                    continue;
                }
            }
        }

        return array_merge($missingTranslations, $availableTranslations);
    }

    private function createEntry($defaultTranslation, $targetTranslation, $path, $key = null, $jsKey = null) {
        $entry = array();
        $entry['key'] = $this->createEntryKey($path, $key, $jsKey);
        $entry['default'] = $this->createEntryGetTranslation($defaultTranslation, $path, $key, $jsKey);
        $entry['target'] = $this->createEntryGetTranslation($targetTranslation, $path, $key, $jsKey);
        $entry['type'] = ($key === null) ? LocalText::$TYPE_MARKUP : LocalText::$TYPE_ARRAY;
        return $entry;
    }

    function createEntryGetTranslation($translation, $path, $key = null, $jsKey = null) {
        if (!isset($translation[$path])) {
            return '';
        }
        if ($key === null) return $translation[$path]->getContent();

        $translation = $translation[$path]->getContent();
        if (!isset($translation[$key])) return '';
        if ($jsKey === null) return $translation[$key];

        if (!isset($translation[$key][$jsKey])) return '';
        return $translation[$key][$jsKey];
    }

    function createEntryKey($path, $key = null, $jsKey = null) {
        $entryKey = sprintf('translation[%s]', $path);
        if ($key === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $key);
        if ($jsKey === null) return $entryKey;

        $entryKey .= sprintf('[%s]', $jsKey);
        return $entryKey;
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
