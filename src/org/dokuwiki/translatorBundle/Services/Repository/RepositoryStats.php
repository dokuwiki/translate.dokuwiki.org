<?php
namespace org\dokuwiki\translatorBundle\Services\Repository;


use Doctrine\ORM\EntityManager;
use org\dokuwiki\translatorBundle\Entity\LanguageStatsEntity;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Language\LocalText;

class RepositoryStats {

    /**
     * @var EntityManager
     */
    private $entityManager;

    function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function clearStats(RepositoryEntity $entity) {
        $query = $this->entityManager->createQuery('
            DELETE FROM dokuwikiTranslatorBundle:LanguageStatsEntity langStats
            WHERE langStats.repository = :repository
        ');
        $query->setParameter('repository', $entity);
        $query->execute();
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
            $statsEntity->setLanguage($language);
            $this->entityManager->persist($statsEntity);
        }
        $this->entityManager->flush();
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
