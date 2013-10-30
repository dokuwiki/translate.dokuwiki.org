<?php

namespace org\dokuwiki\translatorBundle\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RouteNotFoundExceptionListener implements EventSubscriberInterface {

    private $twig;
    private $logger;

    public function __construct(LoggerInterface $logger, \Twig_Environment $twig) {
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function onKernelException(GetResponseForExceptionEvent $event) {
        if(!$event) {
            $this->logger->err("Unknown kernel.exception in ".__CLASS__);
            return;
        }
        $notFoundException = '\Symfony\Component\HttpKernel\Exception\NotFoundHttpException';

        $e = $event->getException();
        $type = get_class($e);
        if ($e instanceof $notFoundException) {
            $this->logger->info($e->getMessage());
            $response = new Response($this->twig->render('dokuwikiTranslatorBundle:Error:404.html.twig'), 404);
            $event->setResponse($response);
            return;
        }

        $this->logger->err("kernel.exception of type $type. Message: '".$e->getMessage()."'\nFile: ".$e->getFile().", line ".$e->getLine()."\nTrace: ".$e->getTraceAsString());
    }

    public static function getSubscribedEvents()
    {
        return array('kernel.exception', 'onKernelException');
    }
}