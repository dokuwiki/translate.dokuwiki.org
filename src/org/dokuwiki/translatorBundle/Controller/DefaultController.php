<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class DefaultController extends Controller {
    public function indexAction() {
        $data['currentLanguage'] = $this->get('language_manager')->getLanguage($this->getRequest());
        $data['coreRepository'] = $this->getCoreRepositoryInformation($data['currentLanguage']);
        $data['repositories'] = $this->getRepositoryInformation($data['currentLanguage']);
        $data['languages'] = $this->getAvailableLanguages();

        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    private function getCoreRepositoryInformation($language) {
        /**
         * @var Query $query
         */
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT stats.completionPercent, repository.displayName
             FROM dokuwikiTranslatorBundle:LanguageStatsEntity stats
             JOIN stats.repository repository
             WHERE repository.type = :type
             AND stats.language = :language');

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);
        $query->setParameter('language', $language);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return array(
                'completionPercent' => 0,
                'name' => 'DokuWiki',
                'displayName' => 'DokuWiki'
            );
        }
    }

    private function getRepositoryInformation($language) {
        /**
         * @var Query $query
         */
        $query = $this->getDoctrine()->getManager()->createQuery('
            SELECT stats.completionPercent, repository.name, repository.displayName
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            LEFT OUTER JOIN repository.translations stats
            WITH (stats.language = :language OR stats.language IS NULL)
            WHERE repository.type != :type
            AND repository.state = :state
            ORDER BY repository.popularity DESC
            '
        );

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);
        $query->setParameter('state', RepositoryEntity::$STATE_ACTIVE);
        $query->setParameter('language', $language);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

    private function getAvailableLanguages() {
        try {
            return $this->getDoctrine()->getManager()->createQuery('
                SELECT languageName
                FROM dokuwikiTranslatorBundle:LanguageNameEntity languageName
                ORDER BY languageName.name ASC
            ')->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

    public function showAction() {
        $data = array();
        $query = $this->getDoctrine()->getManager()->createQuery('
            SELECT repository, translations, lang
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            JOIN repository.translations translations
            JOIN translations.language lang
            WHERE repository.type = :type
        ');

        $query->setParameter('type', RepositoryEntity::$TYPE_CORE);
        $data['repository'] = $query->getSingleResult();

        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig', $data);
    }
}
