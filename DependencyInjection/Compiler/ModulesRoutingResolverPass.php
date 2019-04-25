<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;

/**
 * Обход всех модулей и создание сервисов роутингов для каждого.
 */
class ModulesRoutingResolverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var \Monolith\Bundle\CMSBundle\Module\ModuleBundle $moduleBundle */
        foreach ($container->getParameter('monolith_cms.modules_paths') as $moduleName => $modulePath) {
            // Обработка routing.yml
            $routingConfig = $modulePath.'/Resources/config/routing.yml';
            if (file_exists($routingConfig) and is_array(Yaml::parse(file_get_contents($routingConfig)))) {
                $definition = new Definition(
                    'Symfony\\Component\\Routing\\Router', [
                        new Reference('routing.loader'),
                        $routingConfig, [
                            'cache_dir' => $container->getParameter('kernel.cache_dir').'/monolith_cms',
                            'debug'     => $container->getParameter('kernel.debug'),
                            'matcher_cache_class'   => 'CMSModule'.$moduleName.'UrlMatcher',
                            'generator_cache_class' => 'CMSModule'.$moduleName.'UrlGenerator',
                        ],
                    ]
                );
                $definition->addTag('cms_router_module');
                $definition->setPublic(true);

                $container->setDefinition('cms.router_module.'.strtolower($moduleName), $definition);
            }

            // Обработка routing_admin.yml
            $routingConfig = $modulePath.'/Resources/config/routing_admin.yml';
            if (file_exists($routingConfig) and is_array(Yaml::parse(file_get_contents($routingConfig)))) {
                $definition = new Definition(
                    'Symfony\\Component\\Routing\\Router', [
                        new Reference('routing.loader'),
                        $routingConfig, [
                            'cache_dir' => $container->getParameter('kernel.cache_dir').'/monolith_cms',
                            'debug'     => $container->getParameter('kernel.debug'),
                            'matcher_cache_class'   => 'CMSModule'.$moduleName.'AdminUrlMatcher',
                            'generator_cache_class' => 'CMSModule'.$moduleName.'AdimnUrlGenerator',
                        ],
                    ]
                );
                $definition->addTag('cms_router_module_admin');
                $definition->setPublic(true);

                // Сохранение списка сервисов маршрутов, чтобы можно было быстро перебрать их на название роутов.
                $cms_router_module_admin = $container->hasParameter('cms_router_module_admin')
                    ? $container->getParameter('cms_router_module_admin')
                    : [];

                $serviceName = 'cms.router_module.'.strtolower($moduleName).'.admin';

                $cms_router_module_admin[$moduleName] = $serviceName;
                $container->setParameter('cms_router_module_admin', $cms_router_module_admin);

                $container->setDefinition($serviceName, $definition);
            }

            // Обработка routing_api.yml
            $routingConfig = $modulePath.'/Resources/config/routing_api.yml';
            if (file_exists($routingConfig) and is_array(Yaml::parse(file_get_contents($routingConfig)))) {
                $definition = new Definition(
                    'Symfony\\Component\\Routing\\Router', [
                        new Reference('routing.loader'),
                        $routingConfig, [
                            'cache_dir' => $container->getParameter('kernel.cache_dir').'/monolith_cms',
                            'debug'     => $container->getParameter('kernel.debug'),
                            'matcher_cache_class'   => 'CMSModule'.$moduleName.'ApiUrlMatcher',
                            'generator_cache_class' => 'CMSModule'.$moduleName.'ApiUrlGenerator',
                        ],
                    ]
                );
                $definition->addTag('cms_router_module_api');
                $definition->setPublic(true);

                $container->setDefinition('cms.router_module_api.'.strtolower($moduleName), $definition);
            }
        }
    }
}
