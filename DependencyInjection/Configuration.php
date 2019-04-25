<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cms');

        $rootNode
            ->children()
                ->scalarNode('cache_provider')->defaultValue('cache.provider.monolith_cms_fs')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
