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
                $this->addPlugin($repository);
                $data['repository'] = $repository;
                return $this->render('dokuwikiTranslatorBundle:Plugin:added.html.twig', $data);
            }
        }

        $data['form'] = $form->createView();

        return $this->render('dokuwikiTranslatorBundle:Plugin:add.html.twig', $data);
    }

    private function addPlugin(RepositoryEntity &$repository) {
        $api = $this->get('doku_wiki_repository_api');

        $api->mergePluginInfo($repository);
        $repository->setLastUpdate(0);
        $repository->setState(RepositoryEntity::$STATE_WAITING_FOR_APPROVAL);
        $repository->setActivationKey($this->generateActivationKey($repository));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($repository);
        $entityManager->flush();

        $message = \Swift_Message::newInstance();
        $message->setSubject('Registration');
        $message->setTo($repository->getEmail());
        $message->setFrom($this->container->getParameter('mailer_from'));
        $data = array(
            'repository' => $repository,
        );
        $message->setBody($this->renderView('dokuwikiTranslatorBundle:Mail:pluginAdded.txt.twig', $data));
        $this->get('mailer')->send($message);
    }

    private function generateActivationKey(RepositoryEntity $repository) {
        return md5($repository->getName() . time());
    }
}
