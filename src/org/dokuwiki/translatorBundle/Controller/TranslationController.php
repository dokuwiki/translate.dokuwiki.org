<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use org\dokuwiki\translatorBundle\Services\Repository\RepositoryManager;

class TranslationController extends Controller {

    public function translateCoreAction() {
        $repositoryManager = $this->get('repository_manager');
        $language = $this->get('language_manager')->getLanguage($this->getRequest());
        $repositoryEntity = $this->getDoctrine()->getManager()->getRepository('dokuwikiTranslatorBundle:RepositoryEntity')
            ->getCoreRepository();
        $repository = $repositoryManager->getRepository($repositoryEntity);

        $data['name'] = $repositoryEntity->getDisplayName();
        $data['defaultLanguage'] = $repository->getLanguage('en');
        $data['targetLanguage'] = $repository->getLanguage($language);
        $data['targetLanguageName'] = $language;

        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig',
                $data);
    }

    public function translatePluginAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Translate:translate.html.twig',
                array('name' => $name));
    }

}
