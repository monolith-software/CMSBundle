<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Monolith\Bundle\CMSBundle\Entity\Language;
use Monolith\Bundle\CMSBundle\Entity\Site;
use Monolith\Bundle\CMSBundle\Form\Type\SiteFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @Security("is_granted('ROLE_ADMIN_LANGUAGE') or has_role('ROLE_SUPER_ADMIN')")
 */
class AdminSiteController extends Controller
{
    public function indexAction(): Response
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $sites = $em->getRepository(Site::class)->findAll();

        return $this->render('@CMS/Admin/Site/index.html.twig', [
            'sites' => $sites,
        ]);
    }

    public function createAction(Request $request): Response
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $language = $em->getRepository(Language::class)->findOneBy([], ['position' => 'ASC']);

        $site = new Site();
        $site
            ->setLanguage($language)
            ->setUser($this->getUser())
        ;

        $form = $this->createForm(SiteFormType::class, $site);
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);
        $form->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('cms_admin_site');
            }

            if ($form->get('create')->isClicked() and $form->isValid()) {
                $this->persist($form->getData(), true);

                $this->addFlash('success', 'Site добавлен.');

                return $this->redirectToRoute('cms_admin_site');
            }
        }

        return $this->render('@CMS/Admin/Site/create.html.twig', [
            'form'    => $form->createView(),
        ]);
    }

    public function editAction(Request $request, Site $site): Response
    {
        $form = $this->createForm(SiteFormType::class, $site);
        $form->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);
        $form->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('cms_admin_site');
            }

            if ($form->get('update')->isClicked() and $form->isValid()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Site обновлён.');

                return $this->redirectToRoute('cms_admin_site');
            }
        }

        return $this->render('@CMS/Admin/Site/edit.html.twig', [
            'form'    => $form->createView(),
        ]);
    }
}
