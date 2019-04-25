<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Container
{
    /**
     * @var ContainerInterface
     */
    private static $container;

    /**
     * @param  string $name
     *
     * @return mixed
     */
    public static function get(string $name)
    {
        return self::$container->get($name);
    }

    /**
     * @param  string $name
     *
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::$container->has($name);
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    /**
     * @param  string $name
     *
     * @return mixed
     */
    public static function getParameter(string $name)
    {
        return self::$container->getParameter($name);
    }

    /**
     * @param  string $name
     *
     * @return bool
     */
    public static function hasParameter(string $name): bool
    {
        return self::$container->hasParameter($name);
    }
}
