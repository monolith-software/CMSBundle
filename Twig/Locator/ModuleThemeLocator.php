<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Twig\Locator;

use Liip\ThemeBundle\Locator\FileLocator;

class ModuleThemeLocator extends FileLocator
{
    /** @var string|null */
    protected $moduleTheme;

    /**
     * @param string $moduleTheme
     *
     * @return $this
     */
    public function setModuleTheme(?string $moduleTheme): ModuleThemeLocator
    {
        $this->moduleTheme = $moduleTheme;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModuleTheme(): ?string
    {
        return $this->moduleTheme;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function getPathsForBundleResource($parameters): array
    {
        $parameters['%theme_dir%']    = $this->kernel->getBundle('CMSBundle')->getThemeDir();
        $parameters['%site_dir%']     = $this->kernel->getBundle('SiteBundle')->getPath().'/Resources';
        $parameters['%cms_dir%']      = $this->kernel->getBundle('CMSBundle')->getPath().'/Resources';
        $parameters['%module_theme%'] = $this->moduleTheme;

        return parent::getPathsForBundleResource($parameters);
    }

    protected function getPathsForAppResource($parameters)
    {
        $parameters['%theme_dir%']    = $this->kernel->getBundle('CMSBundle')->getThemeDir();

        return parent::getPathsForAppResource($parameters);
    }
}
