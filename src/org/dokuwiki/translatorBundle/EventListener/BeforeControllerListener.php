<?php

namespace org\dokuwiki\translatorBundle\EventListener;

use org\dokuwiki\translatorBundle\Controller\InitializableController;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * @author Matt Drollette <matt@drollette.com>
 */
class BeforeControllerListener {
    public function onKernelController(FilterControllerEvent $event) {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        $controllerObject = $controller[0];

        if ($controllerObject instanceof ExceptionController) {
            return;
        }

        if ($controllerObject instanceof InitializableController) {
            $controllerObject->initialize($event->getRequest());
        }
    }
}