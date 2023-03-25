<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

interface InitializableController {
    public function initialize(Request $request);
}
