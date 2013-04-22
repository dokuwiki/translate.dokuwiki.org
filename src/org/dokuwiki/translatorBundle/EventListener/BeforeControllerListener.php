<?php

namespace org\dokuwiki\translatorBundle\EventListener;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use org\dokuwiki\translatorBundle\Controller\InitializableController;

/**
 * @author Matt Drollette <matt@drollette.com>
 */
class BeforeControllerListener
{
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            // not a object but a different kind of callable. Do nothing
            return;
        }

        $controllerObject = $controller[0];

        // skip initializing for exceptions
        if ($controllerObject instanceof ExceptionController) {
            return;
        }

        if ($controllerObject instanceof InitializableController) {
            // this method is the one that is part of the interface.
            $controllerObject->initialize($event->getRequest());
        }
    }
}