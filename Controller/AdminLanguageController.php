<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Monolith\Bundle\CMSBundle\Entity\Language;
use Monolith\Bundle\CMSBundle\Form\Type\LanguageFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ADMIN_LANGUAGE') or has_role('ROLE_SUPER_ADMIN')")
 */
class AdminLanguageController extends Controller
{
    //use ControllerTrait;

    public function indexAction(): Response
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $languages = $em->getRepository(Language::class)->findAll();

        return $this->render('@CMS/Admin/Language/index.html.twig', [
            'languages' => $languages,
        ]);
    }

    public function createAction(Request $request): Response
    {
        $form = $this->createForm(LanguageFormType::class, new Language());
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);
        $form->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('cms_admin_language');
            }

            if ($form->get('create')->isClicked() and $form->isValid()) {
                /** @var Language $language */
                $language = $form->getData();
                $language->setUser($this->getUser());

                $this->persist($language, true);

                $this->addFlash('success', 'Язык добавлен.');

                return $this->redirectToRoute('cms_admin_language');
            }
        }

        return $this->render('@CMS/Admin/Language/create.html.twig', [
            'form'    => $form->createView(),
        ]);
    }

    public function editAction(Request $request, Language $language): Response
    {
        $form = $this->createForm(LanguageFormType::class, $language);
        $form->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);
        $form->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('cms_admin_language');
            }

            if ($form->get('update')->isClicked() and $form->isValid()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Язык обновлён.');

                return $this->redirectToRoute('cms_admin_language');
            }
        }

        return $this->render('@CMS/Admin/Language/edit.html.twig', [
            'form'    => $form->createView(),
        ]);
    }
}
