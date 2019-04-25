<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @todo убрать в другое место, потому что зависит от сайта
 */
class DefaultRegionCreatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->get('cms.region')->checkForDefault();
    }
}
