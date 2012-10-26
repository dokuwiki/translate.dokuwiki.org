<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $name = "tom";
        return $this->render('dokuwikiTranslatorBundle:Default:index.html.twig', array('name' => $name));
    }
}
