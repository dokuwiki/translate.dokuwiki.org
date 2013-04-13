<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Repository\Repository;

class DefaultController extends Controller {
    public function indexAction() {
        $data['currentLanguage'] = $this->getLanguage();
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

        $query->setParameter('type', Repository::$TYPE_CORE);
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

        $query->setParameter('type', Repository::$TYPE_CORE);
        $query->setParameter('state', RepositoryEntity::$STATE_ACTIVE);
        $query->setParameter('language', $language);

        try {
            return $query->getResult();
        } catch (NoResultException $e) {
            return array();
        }
    }

    private function getLanguage() {
        $language = $this->getRequest()->query->get('lang', null);
        if ($language !== null) {
            $this->getRequest()->getSession()->set('language', $language);
            return $language;
        }
        $sessionLanguage = $this->getRequest()->getSession()->get('language');
        if ($sessionLanguage !== null) {
            return $sessionLanguage;
        }
        $languages = $this->getRequest()->getLanguages();
        if (empty($languages)) {
            $this->getRequest()->getSession()->set('language', 'en');
            return 'en';
        }
        $pos = strpos($languages[0], '_');
        if ($pos !== false) {
            $languages[0] = substr($languages[0], 0, $pos);
        }
        $language = strtolower($languages[0]);
        $this->getRequest()->getSession()->set('language', $language);
        return $language;
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

    public function showAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig',
            array('name' => $name));
    }
}
