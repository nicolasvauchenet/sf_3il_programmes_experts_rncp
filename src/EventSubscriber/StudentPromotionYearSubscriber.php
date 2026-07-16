<?php

namespace App\EventSubscriber;

use App\Service\Context\Provider\AvailableFrameworksProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final readonly class StudentPromotionYearSubscriber
{
    public function __construct(
        private AvailableFrameworksProvider $availableFrameworksProvider,
        private RouterInterface $router,
        private Security $security,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if ($user === null || !in_array('ROLE_STUDENT', $user->getRoles(), true)) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!is_string($route) || !str_starts_with($route, 'app_promotion_')) {
            return;
        }

        $promotion = (string) $request->query->get('promotion', '');
        $year = (string) $request->query->get('year', '');

        if ($promotion === '' || $year === '') {
            return;
        }

        $latestYear = $this->availableFrameworksProvider->findLatestAcademicYearForPromotion($promotion);

        if ($latestYear === null || $year === $latestYear) {
            return;
        }

        $routeParams = $request->attributes->get('_route_params');
        $params = is_array($routeParams) ? $routeParams : [];
        $params = array_merge($params, $request->query->all(), ['year' => $latestYear]);

        $event->setResponse(new RedirectResponse($this->router->generate($route, $params)));
    }
}
