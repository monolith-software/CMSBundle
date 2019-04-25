<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Controller;

use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LayerController extends Controller
{
    public function indexAction($payload, Request $request): Response
    {
        return $this->render('@CMS/Admin/Layer/index.html.twig', [
            'payload'   => $payload
        ]);
    }
}
