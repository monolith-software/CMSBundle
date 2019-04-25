<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Twig;

use DeviceDetector\DeviceDetector;
use Monolith\Bundle\CMSBundle\CMSAppKernel;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Entity\Site;
use Monolith\Bundle\CMSBundle\Manager\ContextManager;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CmsExtension extends AbstractExtension
{
    use ContainerAwareTrait;
    use ControllerTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cms_current_folder',          [$this, 'getCurrentFolder']),
            new TwigFunction('cms_folder',                  [$this, 'getFolder']),
            new TwigFunction('cms_folder_path',             [$this, 'generateFolderPath']),
            new TwigFunction('cms_node_render',             [$this, 'nodeRender']),
            new TwigFunction('cms_nodes_count_in_region',   [$this, 'nodesCountInRegion']),
            new TwigFunction('cms_get_notifications',       [$this, 'getNotifications']),
            new TwigFunction('cms_version',                 [$this, 'getCMSKernelVersion']),
            new TwigFunction('cms_context_set',             [$this, 'cmsContextSet']),
            new TwigFunction('cms_sites_switcher',          [$this, 'cmsSiteSwitcher']),
            new TwigFunction('cms_context',                 [$this, 'cmsContext']),
            new TwigFunction('cms_device',                  [$this, 'getDevice']),
            new TwigFunction('cms_users_count_in_group',    [$this, 'getUsersCountInGroup']),
        ];
    }

    /**
     * @return Site[]
     */
    public function cmsSiteSwitcher()
    {
        return $this->container->get('cms.context')->getSiteSwitcher();
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function cmsContextSet(string $key, $value): void
    {
        $this->container->get('cms.context')->set($key, $value);
    }

    /**
     * @return ContextManager
     */
    public function cmsContext(): ContextManager
    {
        return $this->container->get('cms.context');
    }

    /**
     * Получение текущей папки.
     *
     * @param string|null $field
     *
     * @return null|Folder|string
     */
    public function getCurrentFolder(string $field = null)
    {
        $folder = $this->container->get('cms.folder')->get($this->container->get('cms.context')->getCurrentFolderId());

        if (!empty($field)) {
            $method = 'get'.ucfirst($field);

            if (method_exists($folder, $method)) {
                return $folder->$method();
            }
        }

        return $folder;
    }

    /**
     * Получение папки.
     *
     * @param int $folderId
     * @param string|null $field
     *
     * @return null|Folder|string
     */
    public function getFolder(int $folderId = null, string $field = null)
    {
        $folder = $this->container->get('cms.folder')->get($folderId);

        if (!empty($field)) {
            $method = 'get'.ucfirst($field);

            if (method_exists($folder, $method)) {
                return $folder->$method();
            }
        }

        return $folder;
    }

    /**
     * Получение полной ссылки на папку, указав её id. Если не указать ид папки, то вернётся текущий путь.
     *
     * @param mixed|null $data
     *
     * @return string
     */
    public function generateFolderPath($data = null): string
    {
        return $this->container->get('cms.folder')->getUri($data);
    }

    /**
     * @param int   $nodeId
     * @param array $route_params
     *
     * @return string
     */
    public function nodeRender(int $nodeId, array $route_params = []): string
    {
        $node = $this->container->get('cms.node')->get($nodeId);

        if (empty($node) or $node->isDeleted() or $node->isNotActive()) {
            return "Node #$nodeId is disabled";
        }

        $moduleResponse = $this->forward($node->getId(), [
            '_route' => 'cms_frontend_run',
            '_route_params' => $node->getParams() + $route_params,
        ]);

        // Обрамление ноды пользовательским кодом.
        $moduleResponse->setContent($node->getCodeBefore().$moduleResponse->getContent().$node->getCodeAfter());

        return $moduleResponse->getContent();
    }

    /**
     * @param  Region|int $region
     *
     * @return int|mixed
     */
    public function nodesCountInRegion($region)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository(Node::class)->countInRegion($region);
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        $data = [];

        foreach ($this->container->get('cms.module')->all() as $module) {
            $notices = $module->getNotifications();

            if (!empty($notices)) {
                $data['notifications'][$module->getName()] = $notices;
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getCMSKernelVersion(): string
    {
        return CMSAppKernel::VERSION;
    }

    /**
     * @return DeviceDetector
     */
    public function getDevice(): DeviceDetector
    {
        $userAgent = $this->container->get('request_stack')->getMasterRequest()->headers->get('user-agent');

        $dd = new DeviceDetector($userAgent);
        $dd->setCache(new \Doctrine\Common\Cache\PhpFileCache(
                $this->container->getParameter('kernel.cache_dir').'/device_detector')
        );
        $dd->skipBotDetection();
        $dd->parse();

        return $dd;
    }

    /**
     * @param $group
     *
     * @return int
     *
     * @todo убрать в репозиторий
     */
    public function getUsersCountInGroup($group): int
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $query = $em->createQuery('
            SELECT COUNT(u.id)
            FROM SiteBundle:User AS u
            JOIN u.groups AS g
            WHERE g.id = :group
        ')->setParameter('group', $group);

        return (int) $query->getSingleScalarResult();
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'monolith_cms_twig_extension';
    }
}
