<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        try {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $container->get('doctrine.orm.entity_manager');
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            if ($container->getParameter('kernel.debug')) {
                echo __CLASS__.': Unavailable DB connection. Please fix it and rebuild cache.';
            }

            return;
        }

        try {
            $container->get('cms.security')->warmupDatabase();
            $container->get('cms.security')->checkDefaultUserGroups();
        } catch (TableNotFoundException $e) {
            // @todo
        }
    }
}
