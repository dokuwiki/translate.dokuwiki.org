<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;

class DefaultController extends Controller {
    public function indexAction() {

        $entityManager = $this->getDoctrine()->getManager();
        $data['coreRepository'] = $entityManager->createQuery(
            'SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.type = \'core\'')->getSingleResult();

        $query = $entityManager->createQuery('SELECT repository
             FROM dokuwikiTranslatorBundle:RepositoryEntity repository
             WHERE repository.type != \'core\'
             AND repository.state = :state
             ORDER BY repository.popularity DESC
             '
        );
        $query->setParameter('state', RepositoryEntity::$STATE_ACTIVE);

        $data['repositories'] = $query->getResult();
        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', $data);
    }

    public function showAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig',
            array('name' => $name));
    }
}
