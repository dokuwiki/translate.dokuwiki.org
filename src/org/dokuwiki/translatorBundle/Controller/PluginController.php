<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use org\dokuwiki\translatorBundle\Entity\RepositoryEntity;
use org\dokuwiki\translatorBundle\Form\RepositoryCreateType;

class PluginController extends Controller {

    public function indexAction(Request $request) {

        $data = array();

        $repository = new RepositoryEntity();
        $repository->setEmail('');
        $repository->setUrl('');
        $repository->setBranch('master');

        $form = $this->createForm(new RepositoryCreateType(), $repository);

        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                // FIXME prepare repository with information from dokuwiki api
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($repository);
                $entityManager->flush();
                return $this->redirect($this->generateUrl('dokuwiki_translator_homepage'));
            }
        }

        $data['form'] = $form->createView();

        return $this->render('dokuwikiTranslatorBundle:Plugin:add.html.twig', $data);
    }
}
