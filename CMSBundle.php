<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle;

use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\ChangeRouterClassPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\DefaultRegionCreatorPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\DeprecationsFixesCompilerPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\FormPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\LiipThemeLocatorsPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\ModulesRoutingResolverPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\PermissionsPass;
use Monolith\Bundle\CMSBundle\DependencyInjection\Compiler\TwigLoaderPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CMSBundle extends Bundle
{
    public function boot(): void
    {
        Container::setContainer($this->container);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('container.build_id', '39thod7hkdfg973rf'); // Фикс с версии symfony v3.4.3

        $container->addCompilerPass(new ChangeRouterClassPass());
        $container->addCompilerPass(new TwigLoaderPass());
        $container->addCompilerPass(new LiipThemeLocatorsPass());
        $container->addCompilerPass(new ModulesRoutingResolverPass());
        $container->addCompilerPass(new FormPass());
        $container->addCompilerPass(new DeprecationsFixesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new DefaultRegionCreatorPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new PermissionsPass(), PassConfig::TYPE_AFTER_REMOVING);
    }

    public function getThemeDir()
    {
        $currentTheme = $this->container->get('cms.context')->getSite()->getTheme();

        return $this->container->getParameter('kernel.project_dir').'/themes/'.$currentTheme;
    }
}
