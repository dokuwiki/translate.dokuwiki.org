<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig');
    }

    public function showAction($name) {
        return $this->render('dokuwikiTranslatorBundle:Default:show.html.twig',
            array('name' => $name));
    }
}
