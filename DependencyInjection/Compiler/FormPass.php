<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resources = $container->getParameter('twig.form.resources');

        $resources[] = 'CMSBundle:Form:fields.html.twig';

        $container->setParameter('twig.form.resources', $resources);
    }
}
