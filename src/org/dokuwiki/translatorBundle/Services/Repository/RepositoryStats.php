<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntity;
use org\dokuwiki\translatorBundle\Entity\LanguageNameEntityRepository;
use org\dokuwiki\translatorBundle\Entity\LanguageStatsEntity;
use org\dokuwiki\translatorBundle\Entity\LanguageStatsEntityRepository;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Language\LocalText;

class RepositoryStats {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var LanguageStatsEntityRepository
     */
    private $languageStatsRepository;

    /**
     * @var LanguageNameEntityRepository
     */
    private $languageNameRepository;

    function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
        $this->languageStatsRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:LanguageStatsEntity');
        $this->languageNameRepository = $entityManager->getRepository('dokuwikiTranslatorBundle:LanguageNameEntity');
    }

    public function clearStats(RepositoryEntity $entity) {
        $this->languageStatsRepository->clearStats($entity);
    }

    /**
     * @param array $translations combined array with all translations
     * @param RepositoryEntity $repository Repository the translation belongs to
     */
    public function createStats($translations, RepositoryEntity $repository) {
        $scores = array();
        if (!isset($translations['en'])) {
            echo 'none created';
            return;
        }

        foreach ($translations as $language => $translation) {
            $scores[$language] = $this->calcStatsForLanguage($translation);
        }

        $max = $scores['en'];
        foreach ($scores as $language => $score) {
            $statsEntity = new LanguageStatsEntity();
            $statsEntity->setRepository($repository);
            $statsEntity->setCompletionPercent((100*$score) / $max);
            $statsEntity->setLanguage($this->getLanguageEntityByCode($language));
            $this->entityManager->persist($statsEntity);
        }
        $this->entityManager->flush();
    }

    private function getLanguageEntityByCode($languageCode) {
        try {
            return $this->languageNameRepository->getLanguageByCode($languageCode);
        } catch (NoResultException $e) {
            $languageName = new LanguageNameEntity();
            $languageName->setCode($languageCode);
            $languageName->setName($languageCode);
            $this->entityManager->persist($languageName);
            return $languageName;
        }
    }

    private function calcStatsForLanguage($translation) {
        $value = 0;
        foreach ($translation as $path => $languageFile) {
            $value += $this->getTranslationValue($languageFile);
        }
        return $value;
    }

    private function getTranslationValue($languageFile) {
        if ($languageFile->getType() == LocalText::$TYPE_MARKUP) {
            return 1;
        }
        return count($languageFile->getContent());
    }

}
