<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Listener;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SiteSwitchListener
{
    use ContainerAwareTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function onRequest(GetResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $request = $event->getRequest();

            if ($switch_site_token = $request->query->get('switch_site_token')) {
                $token_data = $this->container->get('doctrine_cache.providers.cms')->fetch($switch_site_token);

                $security = $this->container->get('security.token_storage');

                $securityToken = $security->getToken();

                $current_user_id = null;
                if ($securityToken and $securityToken->getUser() instanceof UserInterface) {
                    $current_user_id = $securityToken->getUser()->getId();
                }

                if ($token_data !== false and isset($token_data['user_id']) and $token_data['user_id'] != $current_user_id) {
                    /** @var \Doctrine\ORM\EntityManager $em */
                    $em = $this->container->get('doctrine.orm.entity_manager');

                    $userToLogin = $em->find('SiteBundle:User', $token_data['user_id']);

                    $this->container->get('fos_user.security.login_manager')->logInUser('main', $userToLogin);
                }

                $response = new RedirectResponse($request->getBaseUrl().$request->getPathInfo());

                $event->setResponse($response);
            }
        }
    }
}
