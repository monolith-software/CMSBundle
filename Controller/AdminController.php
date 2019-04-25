<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class AdminController extends Controller
{
    public function dashboardAction(): Response
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->render('@CMS/Admin/login.html.twig');
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $userTest = $em->find('SiteBundle:User', 1);

//        dump($userTest);

//        $user = $this->getUser();

//        $r = $this->get('fos_user.security.login_manager')->logInUser('main', $userTest);

//        dump($r);

//        dump($user);

//        $token = $this->get('doctrine_cache.providers.cms')->fetch('switch_site_token');

        return $this->render('@CMS/Admin/dashboard.html.twig');
    }

    /**
     * Render Elfinder FileManager.
     *
     * @return Response
     */
    public function elfinderAction(): Response
    {
        return $this->render('@CMS/Admin/elfinder.html.twig', [
            'fullscreen'    => true,
            'includeAssets' => $this->container->getParameter('fm_elfinder')['instances']['default']['include_assets'],
            'prefix'        => $this->container->getParameter('fm_elfinder')['assets_path'],
            'theme'         => $this->container->getParameter('fm_elfinder')['instances']['default']['theme'],
        ]);
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function switchSelectedSiteAction(Request $request): RedirectResponse
    {
        $site_id = $request->request->get('site', 0);
        $route   = $request->request->get('route', 'cms_admin_index');

        $switcher = $this->get('cms.context')->getSiteSwitcher();

        try {
            $url = $this->generateUrl($route);
        } catch (RouteNotFoundException $e) {
            $url = $this->generateUrl('cms_admin_index');
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $site = $em->getRepository('CMSBundle:Site')->find((int) $site_id);

        if ($site) {
            if (isset($switcher[$site_id])) {
                $url = $switcher[$site_id]['domain'] . $url;
            } else {
                // @todo если не указан домен
                $url = $site->getDomain()->getName() . $url;
            }
        }

        $token = md5(microtime());

        $data = [
            'token'   => $token,
            'user_id' => $this->getUser()->getId(),
        ];

        $this->get('doctrine_cache.providers.cms')->save($token, $data, 3);

        $redirect = $this->redirect('//'.$url.'?switch_site_token='.$token);

        return $redirect;
    }
}
