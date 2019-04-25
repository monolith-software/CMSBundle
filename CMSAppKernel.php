<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle;

use Monolith\Bundle\CMSBundle\Module\ModuleBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

abstract class CMSAppKernel extends BaseKernel
{
    const VERSION = 'v0.0.1-pre-alpha';

    /** @var string  */
    protected $siteName = null;

    /** @var ModuleBundle[] */
    protected $modules = [];

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    protected function registerCmsDependencyBundles(&$bundles): void
    {
        /**
         * Not support for Symfony 3.x
         *
         * "happyr/slugify-bundle": "*",
         * "mremi/templating-extra-bundle": "dev-master",
         * "jms/debugging-bundle": "dev-master",
         */
        $bundles[] = new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle();
        $bundles[] = new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle();
        $bundles[] = new \Symfony\Bundle\AsseticBundle\AsseticBundle();
        $bundles[] = new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
        $bundles[] = new \Symfony\Bundle\SecurityBundle\SecurityBundle();
        $bundles[] = new \Symfony\Bundle\TwigBundle\TwigBundle();
        $bundles[] = new \Symfony\Bundle\MonologBundle\MonologBundle();
        $bundles[] = new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle();
        $bundles[] = new \Symfony\Bundle\DebugBundle\DebugBundle();

        $bundles[] = new \Cache\AdapterBundle\CacheAdapterBundle();
        //$bundles[] = new \Cache\CacheBundle\CacheBundle(); // User Deprecated: Implementing "Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface" without the "reset()" method is deprecated since version 3.4 and will be unsupported in 4.0 for class "Cache\CacheBundle\DataCollector\CacheDataCollector".
        $bundles[] = new \DMS\Bundle\TwigExtensionBundle\DMSTwigExtensionBundle();
        $bundles[] = new \Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle();
        $bundles[] = new \Endroid\QrCodeBundle\EndroidQrCodeBundle();
        $bundles[] = new \FM\ElfinderBundle\FMElfinderBundle();
        $bundles[] = new \FOS\RestBundle\FOSRestBundle();
        $bundles[] = new \FOS\UserBundle\FOSUserBundle();
        //$bundles[] = new \Genemu\Bundle\FormBundle\GenemuFormBundle(); // - genemu/form-bundle v2.3.0 requires twig/twig ~1.14 -
        $bundles[] = new \JMS\SerializerBundle\JMSSerializerBundle();
        //$bundles[] = new \HWI\Bundle\OAuthBundle\HWIOAuthBundle();
        $bundles[] = new \Knp\Bundle\GaufretteBundle\KnpGaufretteBundle();
        //$bundles[] = new \Knp\Bundle\DisqusBundle\KnpDisqusBundle();
        $bundles[] = new \Knp\Bundle\MenuBundle\KnpMenuBundle();
        //$bundles[] = new \Knp\RadBundle\KnpRadBundle();
        $bundles[] = new \Lsw\ApiCallerBundle\LswApiCallerBundle(); // User Deprecated: Implementing "Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface" without the "reset()" method is deprecated since version 3.4 and will be unsupported in 4.0 for class "RaulFraile\Bundle\LadybugBundle\DataCollector\LadybugDataCollector".
        $bundles[] = new \Liip\ThemeBundle\LiipThemeBundle();
        $bundles[] = new \Liip\ImagineBundle\LiipImagineBundle();
        $bundles[] = new \Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle();
        $bundles[] = new \Misd\PhoneNumberBundle\MisdPhoneNumberBundle();
        $bundles[] = new \Mopa\Bundle\BootstrapBundle\MopaBootstrapBundle();
        $bundles[] = new \Oneup\FlysystemBundle\OneupFlysystemBundle();
        $bundles[] = new \RaulFraile\Bundle\LadybugBundle\RaulFraileLadybugBundle(); // "smart-core/ladybug-bundle": "~0.7", // User Deprecated: Implementing "Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface" without the "reset()" method is deprecated since version 3.4 and will be unsupported in 4.0 for class "RaulFraile\Bundle\LadybugBundle\DataCollector\LadybugDataCollector".

        $bundles[] = new \Smart\CoreBundle\SmartCoreBundle();
        $bundles[] = new \SmartCore\Bundle\AcceleratorCacheBundle\AcceleratorCacheBundle();
        $bundles[] = new \SmartCore\Bundle\DbDumperBundle\SmartDbDumperBundle();
        $bundles[] = new \SmartCore\Bundle\FelibBundle\FelibBundle();
        $bundles[] = new \SmartCore\Bundle\HtmlBundle\HtmlBundle();
        $bundles[] = new \SmartCore\Bundle\MediaBundle\SmartMediaBundle();
        $bundles[] = new \SmartCore\Bundle\RichEditorBundle\SmartRichEditorBundle();
        $bundles[] = new \SmartCore\Bundle\SimpleProfilerBundle\SmartSimpleProfilerBundle();
        $bundles[] = new \SmartCore\Bundle\SeoBundle\SmartSeoBundle();
        //$bundles[] = new \SmartCore\Bundle\SessionBundle\SmartCoreSessionBundle(); @todo подумать над альтернативными способами хранения сессий
        $bundles[] = new \SmartCore\Bundle\SettingsBundle\SmartSettingsBundle();
        //$bundles[] = new \SunCat\MobileDetectBundle\MobileDetectBundle();
        $bundles[] = new \Sonata\IntlBundle\SonataIntlBundle();
        $bundles[] = new \Stfalcon\Bundle\TinymceBundle\StfalconTinymceBundle(); // "stfalcon/tinymce-bundle": "v0.2.1",
        $bundles[] = new \Vich\UploaderBundle\VichUploaderBundle();
        $bundles[] = new \YamilovS\SypexGeoBundle\YamilovsSypexGeoBundle();
        //$bundles[] = new \WhiteOctober\BreadcrumbsBundle\WhiteOctoberBreadcrumbsBundle();
        $bundles[] = new \WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle();
        //$bundles[] = new \Zenstruck\Bundle\ImagineExtraBundle\ZenstruckImagineExtraBundle(); // "zenstruck/imagine-extra-bundle": "*"

        $bundles[] = new \Monolith\Bundle\CMSBundle\CMSBundle();

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new \Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new \Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new \Elao\WebProfilerExtraBundle\WebProfilerExtraBundle();
                $bundles[] = new \Monolith\Bundle\CMSGeneratorBundle\CMSGeneratorBundle();
            }
        }
    }

    /**
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles(): array
    {
        $bundles = [];

        $this->registerMonolithCmsBundles($bundles);

        return $bundles;
    }

    /**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot(): void
    {
        if (true === $this->booted) {
            return;
        }

        parent::boot();

        \Profiler::setKernel($this);
    }

    /**
     * Prepares the ContainerBuilder before it is compiled.
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function prepareContainer(ContainerBuilder $container): void
    {
        parent::prepareContainer($container);

        $modulesPaths = [];
        foreach ($this->modules as $module) {
            $modulesPaths[$module->getShortName()] = $module->getPath();
        }

        $container->setParameter('monolith_cms.modules_paths', $modulesPaths);
        $container->setParameter('monolith_cms.site_name', $this->siteName);
    }

    /**
     * Получить список подключенных модулей CMS.
     *
     * @return ModuleBundle[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @param string $name
     *
     * @return ModuleBundle|null
     */
    public function getModule(string $name): ?ModuleBundle
    {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    protected function registerMonolithCmsBundles(&$bundles)
    {
        $this->registerCmsDependencyBundles($bundles);
        $this->autoRegisterSiteBundle($bundles);
        $this->registerCmsModules($bundles);
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    protected function registerCmsModules(&$bundles): void
    {
        $reflector = new \ReflectionClass(end($bundles)); // Регистрация модулей строго после регистрции SiteBundle
        $modulesConfig = dirname($reflector->getFileName()).'/Resources/config/modules.ini';

        if (file_exists($modulesConfig)) {
            foreach (parse_ini_file($modulesConfig) as $module_class => $is_enabled) {
                if (class_exists($module_class)) {
                    /** @var ModuleBundle $module_bundle */
                    $module_bundle = new $module_class();

                    if ($module_bundle instanceof ModuleBundle) {
                        $module_bundle->setIsEnabled((bool) $is_enabled);
                        $this->modules[$module_bundle->getName()] = $module_bundle;
                        $bundles[] = $module_bundle;
                    } else {
                        throw new \Exception($module_class.' is not instanceof '.ModuleBundle::class);
                    }
                } else {
                    throw new \Exception($module_class.' is not exists.');
                }
            }
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     *
     * @throws \LogicException
     */
    protected function autoRegisterSiteBundle(&$bundles): void
    {
        // Сначала производится попытка подключить указанный вручную сайт.
        if (!empty($this->siteName)) {
            $siteBundleClass = '\\'.$this->siteName.'SiteBundle\SiteBundle';

            if (class_exists($siteBundleClass)) {
                $bundles[] = new $siteBundleClass();

                return;
            }
        }

        $cacheSiteName = $this->getCacheDir().'/monolith_cms_site_name.meta';
        if (file_exists($cacheSiteName)) {
            $this->siteName = file_get_contents($cacheSiteName);
            $siteBundleClass = '\\'.$this->siteName.'SiteBundle\\SiteBundle';
            if (class_exists($siteBundleClass)) {
                $bundles[] = new $siteBundleClass();

                return;
            }
        }

        $finder = (new Finder())->directories()->depth('== 0')->name('*SiteBundle')->name('SiteBundle')->in($this->rootDir.'/../src');

        // Такой подсчет работает быстрее, чем $finder->count();
        $count = 0;
        $dirName = null;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $count++;
            $dirName = $file->getBasename();
        }

        if ($count == 0 and isset($_SERVER['HTTP_HOST'])) {
            die('Не доступен SiteBundle, сгенерируйте его командой <pre>$ bin/console cms:generate:sitebundle</pre>');
        }

        if ($count > 1) {
            $response = 'Trying to register two bundles with the same name "SiteBundle"</br></br>Found in /src/:</br>';
            foreach ($finder as $file) {
                $response .= $file->getBasename().'</br>';
            }

            if (isset($_SERVER['HTTP_HOST'])) {
                die($response);
            } else {
                throw new \LogicException(str_replace('</br>', "\n", $response));
            }
        } else {
            $className = '\\'.$dirName.'\\SiteBundle';
            if (class_exists($className)) {
                $bundles[] = new $className();
                $this->siteName = str_replace('SiteBundle', '', $dirName);

                if (is_dir(dirname($cacheSiteName))) {
                    file_put_contents($cacheSiteName, $this->siteName, LOCK_EX);
                }
            }
        }
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->rootDir.'/config/config_'.$this->getEnvironment().'.yml');
    }

    /**
     * Размещение кеша в /var/.
     */
    public function getCacheDir(): string
    {
        return $this->rootDir.'/../var/cache/'.$this->environment;
    }

    /**
     * Размещение логов в /var/.
     */
    public function getLogDir(): string
    {
        return $this->rootDir.'/../var/logs';
    }
}
