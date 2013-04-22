<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;

interface InitializableController {
    public function initialize(Request $request);
}