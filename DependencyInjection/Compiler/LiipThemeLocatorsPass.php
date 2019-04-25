<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LiipThemeLocatorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('liip_theme.file_locator')->setClass('Monolith\Bundle\CMSBundle\Twig\Locator\ModuleThemeLocator');
        $container->getDefinition('liip_theme.templating_locator')->setClass('Monolith\Bundle\CMSBundle\Twig\Locator\TemplateLocator');
    }
}
