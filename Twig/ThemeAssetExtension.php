<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeAssetExtension extends AbstractExtension
{
    use ContainerAwareTrait;

    private $packages;

    public function __construct(Packages $packages, ContainerInterface $container)
    {
        $this->container = $container;
        $this->packages  = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('theme_asset', array($this, 'getThemeAssetUrl')),
        ];
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string The public path of the asset
     */
    public function getThemeAssetUrl($path, $packageName = null)
    {
        $currentTheme = $this->container->get('cms.context')->getSite()->getTheme();

        $sitePublicPath = $this->packages->getUrl('bundles/site/theme/'.$currentTheme, $packageName);

        return $sitePublicPath.'/'.$path;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'monolith_theme_asset';
    }

}
