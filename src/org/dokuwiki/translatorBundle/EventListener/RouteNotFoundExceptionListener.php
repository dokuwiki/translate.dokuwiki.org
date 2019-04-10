<?php

namespace org\dokuwiki\translatorBundle\EventListener;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class RouteNotFoundExceptionListener implements EventSubscriberInterface {

    private $twig;
    private $logger;

    public function __construct(LoggerInterface $logger, Environment $twig) {
        $this->logger = $logger;
        $this->twig = $twig;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        if(!$event) {
            $this->logger->error("Unknown kernel.exception in ".__CLASS__);
            return;
        }

        $e = $event->getException();
        $type = get_class($e);
        if ($e instanceof NotFoundHttpException) {
            $this->logger->info($e->getMessage());
            $response = new Response($this->twig->render('dokuwikiTranslatorBundle:Error:404.html.twig'), 404);
            $event->setResponse($response);
            return;
        }

        $this->logger->error("kernel.exception of type $type. Message: '".$e->getMessage()."'\nFile: ".$e->getFile().", line ".$e->getLine()."\nTrace: ".$e->getTraceAsString());
    }

    public static function getSubscribedEvents()
    {
        return array('kernel.exception' => 'onKernelException');
    }
}
