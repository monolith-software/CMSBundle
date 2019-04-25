<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Twig\Loader;

use Liip\ThemeBundle\ActiveTheme;
use Liip\ThemeBundle\Twig\Loader\FilesystemLoader as BaseFilesystemLoader;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Twig\Locator\TemplateLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Twig\Error\LoaderError;

class FilesystemLoader extends BaseFilesystemLoader
{
    /**
     * Фикс для Symfony v3.2.1 где 3-им аргументом приходит root_dir.
     *
     * FilesystemLoader constructor.
     *
     * @param FileLocatorInterface        $locator
     * @param TemplateNameParserInterface $parser
     * @param null                        $path
     * @param null                        $activeTheme
     */
    public function __construct(FileLocatorInterface $locator, TemplateNameParserInterface $parser, $path = null, $activeTheme = null)
    {
        if ($path instanceof ActiveTheme) {
            $activeTheme = $path;
        }

        parent::__construct($locator, $parser, $activeTheme);
    }

    /**
     * @param Node|null $node
     */
    public function setModuleTheme(Node $node = null): void
    {
        if ($node) {
            $this->getTemplateLocator()->getLocator()->setModuleTheme($node->getTemplate());
            $this->clearCacheForModule($node->getModule());
        } else {
            $this->getTemplateLocator()->getLocator()->setModuleTheme(null);
        }
    }

    /**
     * @param string $prefix
     */
    public function clearCacheForModule($prefix): void
    {
        if (empty($this->cache)) {
            return;
        }

        $this->getTemplateLocator()->clearCacheForModule($prefix);

        $prefix .= 'ModuleBundle';

        foreach ($this->cache as $tpl => $__dummy_path) {
            if (0 === strpos($tpl, $prefix.':')) {
                unset($this->cache[$tpl]);
            }
        }
    }

    /**
     * @return TemplateLocator
     */
    protected function getTemplateLocator(): TemplateLocator
    {
        return $this->locator;
    }

    /**
     * Добавление пути после app.
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A path name
     *
     * @throws LoaderError
     */
    public function addCmsAppPath(string $path, string $namespace = self::MAIN_NAMESPACE): void
    {
        // invalidate the cache
        $this->cache = $this->errorCache = array();

        if (!is_dir($path)) {
            throw new LoaderError(sprintf('The "%s" directory does not exist.', $path));
        }

        $path = rtrim($path, '/\\');

        if (!isset($this->paths[$namespace])) {
            $this->paths[$namespace][] = $path;
        } else {
            $existAppPathKey = false;

            foreach ($this->paths[$namespace] as $key => $path2) {
                if (strpos($path2, 'app/Resources/')) {
                    $existAppPathKey = $key;
                }
            }

            if ($existAppPathKey === false) {
                array_unshift($this->paths[$namespace], $path);
            } else {
                $newPaths = [];

                foreach ($this->paths[$namespace] as $key => $path2) {
                    $newPaths[] = $path2;

                    if ($key == $existAppPathKey) {
                        $newPaths[] = $path;
                    }
                }

                $this->paths[$namespace] = $newPaths;
            }
        }
    }

    /**
     * @todo сделать пулл реквест с методом getCacheKey
     *
     * Returns the path to the template file.
     *
     * The file locator is used to locate the template when the naming convention
     * is the symfony one (i.e. the name can be parsed).
     * Otherwise the template is located using the locator from the twig library.
     *
     * @param string|TemplateReferenceInterface $template The template
     * @param bool                              $throw    When true, a LoaderError exception will be thrown if a template could not be found
     *
     * @return string|null The path to the template file
     *
     * @throws LoaderError if the template could not be found
     */
    protected function findTemplate($template, $throw = true): ?string
    {
        $logicalName = (string) $template;

        $logicalName .= '|module_theme='.$this->locator->getLocator()->getModuleTheme(); // Добавлена эта строка

        if ($this->activeTheme) {
            $logicalName .= '|'.$this->activeTheme->getName();
        }

        if (isset($this->cache[$logicalName])) {
            return $this->cache[$logicalName];
        }

        $file = null;
        $previous = null;

        try {
            $templateReference = $this->parser->parse($template);
            $file = $this->locator->locate($templateReference);
        } catch (\Exception $e) {
            $previous = $e;

            // for BC
            try {
                $file = parent::findTemplate((string) $template);
            } catch (LoaderError $e) {
                $previous = $e;
            }
        }

        if (false === $file || null === $file) {
            if ($throw) {
                throw new LoaderError(sprintf('Unable to find template "%s".', $logicalName), -1, null, $previous);
            }

            return null;
        }

        return $this->cache[$logicalName] = $file;
    }
}
