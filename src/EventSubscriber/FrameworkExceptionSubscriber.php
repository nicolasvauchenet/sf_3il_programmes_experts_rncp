<?php

namespace App\EventSubscriber;

use App\Exception\FrameworkException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class FrameworkExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof FrameworkException) {
            return;
        }

        $request = $this->requestStack->getSession();
        if ($request !== null) {
            $request->getFlashBag()->add('error', $exception->getMessage());
        }

        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('app_home'))
        );
    }
}
