<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Monolith\Bundle\CMSBundle\Cache\CacheWrapper;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Form\Type\FolderFormType;
use Monolith\Bundle\CMSBundle\Repository\FolderRepository;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Symfony\Component\Form\Form;

class FolderManager
{
    use ContainerAwareTrait;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var FolderRepository
     */
    protected $repository;

    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->cache      = $container->get('cms.cache');
        $this->em         = $container->get('doctrine.orm.entity_manager');
        $this->repository = $this->em->getRepository(Folder::class);
    }

    /**
     * @return Folder
     */
    public function create(): Folder
    {
        return new Folder();
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param mixed $data    The initial data for the form
     * @param array $options Options for the form
     *
     * @return \Symfony\Component\Form\Form
     */
    public function createForm($data = null, array $options = []): Form
    {
        return $this->container->get('form.factory')->create(FolderFormType::class, $data, $options);
    }

    /**
     * Поиск по родительской папке, исключая удалённые.
     *
     * @param Folder $parent_folder
     *
     * @return Folder[]
     */
    public function findByParent(Folder $parent_folder = null)
    {
        return $this->repository->findByParent($parent_folder);
    }

    /**
     * Получение полной ссылки на папку, указав её id. Если не указать ид папки, то вернётся текущий путь.
     *
     * @param  Node|Folder|int|null $data       NULL for current folder Id form cms.context.
     * @param  bool                 $isBaseUrl  Подставлять baseUrl к ссылке.
     *
     * @return string $uri
     *
     * @todo абсолютный урл с портом и схемой.
     */
    public function getUri($data = null, bool $isBaseUrl = true): string
    {
        if (null === $data) {
            $folder_id = $this->container->get('cms.context')->getCurrentFolderId();
        } elseif ($data instanceof Node) {
            $folder_id = $data->getFolderId();
        } elseif ($data instanceof Folder) {
            $folder_id = $data->getId();
        } elseif (intval($data)) {
            $folder_id = $data;
        } else {
            throw new \Exception('Unknown input type.');
        }

        $siteId = $this->container->get('cms.context')->getSiteId();

        $cache_key = md5('site_id='.$siteId.'cms_folder.full_path.'.$folder_id);
        if (null === $uri = $this->cache->get($cache_key)) {
            $uri = '/';
            $uri_parts = [];

            $folder = $this->repository->findOneBy([
                'id'         => $folder_id,
                'is_active'  => true,
            ]);

            if (!empty($folder)) {
                /* @var $folder Folder */
                while ($folder->getParentFolder()) {
                    $folder = $this->repository->findOneBy([
                        'id'         => $folder->getId(),
                        'is_active'  => true,
                    ]);

                    if ($folder and $folder->getParentFolder()) {
                        $uri_parts[] = $folder->getUriPart();
                        $folder = $folder->getParentFolder();
                    } else {
                        break;
                    }
                }
            }

            $uri_parts = array_reverse($uri_parts);
            foreach ($uri_parts as $value) {
                $uri .= $value;

                if (!$folder->isFile()) {
                    $uri .= '/';
                }
            }

            $this->cache->set($cache_key, $uri, ['folder']);
        }

        return $isBaseUrl ? $this->container->get('request_stack')->getMasterRequest()->getBaseUrl().$uri : $uri;
    }

    /**
     * @param Folder $folder
     */
    public function checkRelations(Folder $folder): void
    {
        if (empty($this->container->get('cms.context')->getSite())) {
            return;
        }

        $uriPart = $folder->getUriPart();

        $site = $this->container->get('cms.context')->getSite();

        if (empty($uriPart)
            and $site->getRootFolder() instanceof Folder
            and $folder->getId() != $site->getRootFolder()->getId()
        ) {
            $folder->setUriPart($folder->getId());
        }

        // Защита от цикличных зависимостей.
        $parent = $folder->getParentFolder();

        if (null == $parent) {
            return;
        }

        // Максимальный уровень вложенности 30.
        $cnt = 30;
        $ok = false;
        while ($cnt--) {
            if ($parent->getId() == $this->container->get('cms.context')->getSite()->getRootFolder()->getId()) {
                $ok = true;
                break;
            } else {
                $parent = $parent->getParentFolder();
                continue;
            }
        }

        // Если обнаружена циклическая зависимость, тогда родитель выставляется корневая папка.
        if (!$ok) {
            $folder->setParentFolder($this->container->get('cms.folder')->get($this->container->get('cms.context')->getSite()->getRootFolder()->getId()));
        }
    }

    /**
     * @param null $parent
     *
     * @return string
     */
    public function getStructureHash(?Folder $parent = null): string
    {
        $structure = $this->getStructureArray($parent);

        return md5(serialize($structure));
    }
    
    /**
     * Recursively build tree.
     *
     * @param null $parent
     *
     * @return array
     */
    public function getStructureArray(?Folder $parent = null): array
    {
        $structure = [];

        foreach ($this->findByParent($parent) as $f) {
            if ($f->getParentFolder() === null and $f->getId() !== $this->container->get('cms.context')->getSite()->getRootFolder()->getId()) {
                continue;
            }

            $nodes = [];
            foreach ($f->getNodes() as $node) {
                $nodes[$node->getId()] = [ // @todo сделать у ноды метод getDataAsArray()
                    'id' => $node->getId(),
                    'description' => $node->getDescription(),
                    'module' => $node->getModule(),
                    'module_short_name' => $node->getModuleShortName(),
                    'controller' => $node->getController(),
                    'params' => $node->getParams(),
                    'region' => $node->getRegion()->getName(),
                    'template' => $node->getTemplate(),
                    'code_before' => $node->getCodeBefore(),
                    'code_after' => $node->getCodeAfter(),
                    'position' => $node->getPosition(),
                    'priority' => $node->getPriority(),
                    'permissions' => $node->getPermissionsCache(),
                    'controls_in_toolbar' => $node->getControlsInToolbar(),
                    'is_active' => $node->isActive(),
                    'is_cached' => $node->isCached(),
                    'is_use_eip' => $node->isEip(),
                    'created_at' => $node->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }

            $folder = [ // @todo сделать у папок метод getDataAsArray()
                'id' => $f->getId(),
                'uri_part' => $f->getUriPart(),
                'full_path' => $this->getUri($f),
                'title' => $f->getTitle(),
                'description' => $f->getDescription(),
                'is_file' => $f->isFile(),
                'meta' => $f->getMeta(),
                'redirect_to' => $f->getRedirectTo(),
                'template_inheritable' => $f->getTemplateInheritable(),
                'template_self' => $f->getTemplateSelf(),
                'is_active' => $f->isActive(),
                'permissions' => $f->getPermissionsCache(),
                'created_at' => $f->getCreatedAt()->format('Y-m-d H:i:s'),
                'position' => $f->getPosition(),
                'user' => [
                    'id' => $f->getUser()->getId(),
                    'username' => $f->getUser()->getUsername(),
                ],
                'router_node_id' => $f->getRouterNodeId(), // @todo relations
                'nodes' => $nodes,
                'folders' => $this->getStructureArray($f),
            ];

            $structure[$f->getId()] = $folder;
        }

        return $structure;
    }

    /**
     * @param int $id
     *
     * @return null|Folder
     */
    public function get($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Remove entity.
     *
     * @todo проверку зависимостей от нод и папок.
     */
    public function remove(Folder $entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * @param Folder $folder
     *
     * @return $this
     */
    public function update(Folder $folder)
    {
        $this->em->persist($folder);
        $this->em->flush($folder);

        $uriPart = $folder->getUriPart();

        $site = $this->container->get('cms.context')->getSite();

        // Если не указан сегмент URI, тогда он устанавливается в ID папки.
        if (empty($uriPart)
            and $site->getRootFolder() instanceof Folder
            and $folder->getId() != $site->getRootFolder()->getId()
        ) {
            $folder->setUriPart($folder->getId());
            $this->em->persist($folder);
            $this->em->flush($folder);
        }

        return $this;
    }
}
