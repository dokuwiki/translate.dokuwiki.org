<?php

namespace org\dokuwiki\translatorBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

interface InitializableController {
    public function initialize(Request $request);
}
