<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ThemeManager
{
    use ContainerAwareTrait;

    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    /** @var array of availabled Themes */
    protected $themes = [];

    /** @var Filesystem */
    protected $filesystem;

    /**
     * ThemeManager constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->filesystem = $container->get('filesystem');

        $themesDir = $this->container->getParameter('kernel.project_dir').'/themes/';

        //foreach (scandir($themesDir) as $name) {
        /** @var \SplFileInfo $splFile */
        foreach (Finder::create()->depth(0)->directories()->in($themesDir) as $splFile) {
            $name = $splFile->getFilename();

            if (is_dir($themesDir.$name) and file_exists($themesDir.$name.'/config.yml')) {
                try {
                    $config = Yaml::parse(file_get_contents($themesDir.$name.'/config.yml'));

                    if (isset($config['sites']) and !in_array($this->container->get('cms.context')->getSiteId(), $config['sites']) and !empty($config['sites'])) {
                        continue;
                    }

                    $this->themes[$name] = $config;
                    $this->themes[$name]['screenshots'] = [];
                    $this->themes[$name]['dirname'] = $name;

                    $screenshotsDir = $themesDir.$name.'/screenshots';
                    if (is_dir($screenshotsDir)) {
                        /** @var \SplFileInfo  $screenshot */
                        foreach (Finder::create()->depth(0)->files()->in($screenshotsDir)->sortByName() as $screenshot) {
                            $this->themes[$name]['screenshots'][] = $screenshot->getFilename();
                        }
                    }
                } catch (ParseException $e) {
                    // @todo
                }
            }
        }
    }

    /**
     * @return array of availabled Themes
     */
    public function all(): array
    {
        return $this->themes;
    }

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function get(string $name): ?array
    {
        return isset($this->themes[$name]) ? $this->themes[$name] : null;
    }

    /**
     * @param null|string $moduleName
     *
     * @return array
     * @throws \Exception
     */
    public function getModuleThemes(?string $moduleName): array
    {
        if (empty($moduleName)) {
            return [];
        }

        $currentTheme = $this->container->get('cms.context')->getSite()->getTheme();
        $themeDir     = $this->container->getParameter('kernel.project_dir').'/themes/'.$currentTheme.'/modules/'.$moduleName;

        if (!is_dir($themeDir)) {
            return [];
        }

        $themes = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach (Finder::create()->directories()->sortByName()->depth('== 0')->in($themeDir) as $file) {
            $themes[] = $file->getFilename();
        }

        return $themes;
    }


    /**
     * @param bool $relative
     *
     * @return array
     *
     * @throws \InvalidArgumentException when the bundle is not enabled
     */
    public function createSymlinks(bool $relative = false): array
    {
        $siteBundle = $this->container->get('kernel')->getBundle('SiteBundle');

        // @todo возможность симлинкать ресурсы темы в корень паблика сайтбандла или даже произвольную веб папку.
        $siteBundlePublicDir = $siteBundle->getPath().'/Resources/public/theme/';

        if (is_dir($siteBundlePublicDir)) {
            $dirsToRemove = Finder::create()->depth(0)->directories()->in($siteBundlePublicDir);
            $this->filesystem->remove($dirsToRemove);
        }

        $themesDir = $this->container->getParameter('kernel.project_dir').'/themes/';

        $result = [];
        foreach ($this->all() as $name => $data) {
            if (is_dir($themesDir.$name.'/public')) {
                $originDir = $themesDir.$name.'/public';
                $targetDir = $siteBundlePublicDir.$name;

                if ($relative) {
                    $method = $this->relativeSymlinkWithFallback($originDir, $targetDir);
                } else {
                    $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
                }

                // @todo !!! выбрасывать ошибку, если не получилось сделать симлинку!
                $result[] = [
                    'method' => $method,
                    'theme'  => $name,
                    'target' => str_replace($this->container->getParameter('kernel.project_dir'), '.', $targetDir),
                ];
            }
        }

        return $result;
    }
    
    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function relativeSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir, true);
            $method = self::METHOD_RELATIVE_SYMLINK;
        } catch (IOException $e) {
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Try to create absolute symlink.
     *
     * Falling back to hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function absoluteSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir);
            $method = self::METHOD_ABSOLUTE_SYMLINK;
        } catch (IOException $e) {
            // fall back to copy
            $method = $this->hardCopy($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool   $relative
     *
     * @throws IOException if link can not be created
     */
    private function symlink(string $originDir, string $targetDir, $relative = false)
    {
        if ($relative) {
            $this->filesystem->mkdir(dirname($targetDir));
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }

        $this->filesystem->symlink($originDir, $targetDir);

        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0, null, $targetDir);
        }
    }

    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function hardCopy(string $originDir, string $targetDir): string
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }
}
