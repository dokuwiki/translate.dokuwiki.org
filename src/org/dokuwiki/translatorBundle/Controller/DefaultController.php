<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Services\Repository\Repository;

class DefaultController extends Controller {
    public function indexAction() {
        $data['language'] = $this->getLanguage();

        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->createQuery(
            'SELECT stats.completionPercent, repository.displayName
             FROM dokuwikiTranslatorBundle:LanguageStatsEntity stats
             JOIN stats.repository repository
             WHERE repository.type = \'core\'
             AND stats.language = :language');
        $query->setParameter('language', $data['language']);
        $data['coreRepository'] = $query->getSingleResult();


        $query = $entityManager->createQuery('
            SELECT stats.completionPercent, repository.name, repository.displayName
            FROM dokuwikiTranslatorBundle:RepositoryEntity repository
            LEFT OUTER JOIN repository.translations stats
            WITH (stats.language = :language OR stats.language IS NULL)
            WHERE repository.type != \'core\'
            AND repository.state = :state

            ORDER BY repository.popularity DESC
            '
        );
        $query->setParameter('state', RepositoryEntity::$STATE_ACTIVE);
        $query->setParameter('language', $data['language']);


        $data['repositories'] = $query->getResult();
        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    private function getLanguage() {
        $language = $this->getRequest()->query->get('lang', null);
        if ($language !== null) {
            return $language;
        }
        $languages = $this->getRequest()->getLanguages();
        if (empty($languages)) {
            return 'en';
        }
        $pos = strpos($languages[0], '_');
        if ($pos !== false) {
            $languages[0] = substr($languages[0], 0, $pos);
        }
        return strtolower($languages[0]);
    }

    public function showAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig',
            array('name' => $name));
    }
}
