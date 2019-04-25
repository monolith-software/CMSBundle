<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\CMS\NodeInterface;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Monolith\Bundle\CMSBundle\Tools\FrontControl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Monolith\Bundle\CMSBundle\Repository\NodeRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="cms_nodes",
 *      indexes={
 *          @ORM\Index(columns={"is_active"}),
 *          @ORM\Index(columns={"deleted_at"}),
 *          @ORM\Index(columns={"position"}),
 *          @ORM\Index(columns={"region_id"}),
 *          @ORM\Index(columns={"module"})
 *      }
 * )
 */
class Node implements \Serializable // NodeInterface @todo
{
    // Получать элементы управления для тулбара.
    const TOOLBAR_NO                    = 0; // Никогда
    const TOOLBAR_ONLY_IN_SELF_FOLDER   = 1; // Только в собственной папке
    const TOOLBAR_ALWAYS                = 2; // Всегда

    use ColumnTrait\Id;
    use ColumnTrait\IsActive;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\DeletedAt;
    use ColumnTrait\Description;
    use ColumnTrait\Position;
    use ColumnTrait\FosUser;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    protected $controls_in_toolbar;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank()
     */
    protected $module;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $controller;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=false)
     */
    protected $params;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $template;

    /**
     * @var Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="nodes")
     * @Assert\NotBlank()
     */
    protected $folder;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Region", fetch="EAGER")
     * @Assert\NotBlank()
     */
    protected $region;

    /**
     * @var UserGroup[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="nodes_granted_read", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_permissions_nodes_read")
     * @ORM\OrderBy({"position" = "ASC", "title" = "ASC"})
     */
    protected $groups_granted_read;

    /**
     * @var UserGroup[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="nodes_granted_write", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_permissions_nodes_write")
     * @ORM\OrderBy({"position" = "ASC", "title" = "ASC"})
     */
    protected $groups_granted_write;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $permissions_cache;

    /**
     * Приоритет порядка выполнения.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    protected $priority;

    /**
     * Может ли нода кешироваться.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_cached;

    /**
     * Использовать Edit-In-Place. Если отключить также не будет генерироваться div вокруг ноды.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":1})
     */
    protected $is_use_eip;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $code_before;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $code_after;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    //protected $cache_params;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    //protected $plugins;

    // ================================= Unmapped properties =================================

    /**
     * Хранение folder_id для минимизации кол-ва запросов.
     *
     * @var int|null
     */
    protected $folder_id = null;

    /**
     * @var array
     */
    protected $controller_temp = [];

    /**
     * Is Edit-In-Place enable.
     *
     * @var bool
     */
    protected $eip = false;

    /**
     * @var FrontControl[]
     */
    protected $front_controls = [];

    /**
     * @var string
     */
    protected $region_name = null;

    /**
     * Node constructor.
     */
    public function __construct()
    {
        $this->groups_granted_read  = new ArrayCollection();
        $this->groups_granted_write = new ArrayCollection();
        $this->permissions_cache    = [];
        $this->controls_in_toolbar  = self::TOOLBAR_NO;
        $this->created_at   = new \DateTime();
        $this->is_active    = true;
        $this->is_cached    = false;
        $this->is_use_eip   = true;
        $this->params       = [];
        $this->position     = 0;
        $this->priority     = 0;
    }

    /**
     * Сериализация.
     */
    public function serialize(): string
    {
        $this->getFolderId(); // Lazy load

        return serialize([
            //return igbinary_serialize([
            $this->id,
            $this->is_active,
            $this->is_cached,
            $this->is_use_eip,
            $this->module,
            $this->params,
            $this->code_before,
            $this->code_after,
            $this->folder,
            $this->folder_id,
            clone $this->region,
            $this->region_name,
            $this->permissions_cache,
            $this->position,
            $this->priority,
            $this->template,
            $this->description,
            $this->controls_in_toolbar,
            $this->user,
            $this->created_at,
            $this->deleted_at,
            $this->controller,
            $this->controller_temp,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        list(
            $this->id,
            $this->is_active,
            $this->is_cached,
            $this->is_use_eip,
            $this->module,
            $this->params,
            $this->code_before,
            $this->code_after,
            $this->folder,
            $this->folder_id,
            $this->region,
            $this->region_name,
            $this->permissions_cache,
            $this->position,
            $this->priority,
            $this->template,
            $this->description,
            $this->controls_in_toolbar,
            $this->user,
            $this->created_at,
            $this->deleted_at,
            $this->controller,
            $this->controller_temp
            ) = unserialize($serialized);
        //) = igbinary_unserialize($serialized);
    }

    /**
     * @ORM\PreFlush()
     */
    public function updatePermissionsCache()
    {
        $this->permissions_cache = [];

        foreach ($this->getGroupsGrantedRead() as $group) {
            $this->permissions_cache['read'][$group->getId()] = $group->getName();
        }

        foreach ($this->getGroupsGrantedWrite() as $group) {
            $this->permissions_cache['write'][$group->getId()] = $group->getName();
        }
    }

    /**
     * @return string|null
     */
    public function getCodeBefore(): ?string
    {
        return $this->code_before;
    }

    /**
     * @param string $code_before
     *
     * @return $this
     */
    public function setCodeBefore(?string $code_before): Node
    {
        $this->code_before = $code_before;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCodeAfter(): ?string
    {
        return $this->code_after;
    }

    /**
     * @param string|null $code_after
     *
     * @return $this
     */
    public function setCodeAfter(?string $code_after): Node
    {
        $this->code_after = $code_after;

        return $this;
    }

    /**
     * @param int $controls_in_toolbar
     *
     * @return $this
     */
    public function setControlsInToolbar(int $controls_in_toolbar): Node
    {
        $this->controls_in_toolbar = $controls_in_toolbar;

        return $this;
    }

    /**
     * @return int
     */
    public function getControlsInToolbar(): int
    {
        return $this->controls_in_toolbar;
    }

    /**
     * @param bool $is_cached
     *
     * @return $this
     */
    public function setIsCached(bool $is_cached): Node
    {
        $this->is_cached = $is_cached;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->is_cached;
    }

    /**
     * @param Region $region
     *
     * @return $this
     */
    public function setRegion(Region $region): Node
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion(): ?Region
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getRegionName(): string
    {
        if (null === $this->region_name) {
            $this->region_name = $this->getRegion()->getName();
        }

        return $this->region_name;
    }

    /**
     * @param Folder $folder
     *
     * @return $this
     */
    public function setFolder(Folder $folder): Node
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder
    {
        return $this->folder;
    }

    /**
     * @param string $module
     *
     * @return $this
     *
     * @deprecated скорее всего будет связь с таблицей модулей
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return string
     *
     * @deprecated скорее всего будет связь с таблицей модулей
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     *
     * @deprecated скорее всего будет связь с таблицей модулей
     */
    public function getModuleShortName()
    {
        return substr($this->module, 0, -12);
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params): Node
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return (empty($this->params)) ? [] : $this->params;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        return (isset($this->params[$key])) ? $this->params[$key] : $default;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority(?int $priority): Node
    {
        if (empty($priority)) {
            $priority = 0;
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param string $template
     *
     * @return $this
     */
    public function setTemplate(?string $template): Node
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param null|string $default
     *
     * @return null|string
     */
    public function getTemplate(?string $default = null): ?string
    {
        return empty($this->template) ? $default : $this->template;
    }

    /**
     * @return int
     */
    public function getFolderId(): int
    {
        if ($this->folder_id == null) {
            $this->folder_id = $this->getFolder()->getId();
        }

        return $this->folder_id;
    }

    /**
     * @param bool $eip
     *
     * @return $this
     */
    public function setEip(bool $eip): Node
    {
        $this->eip = $eip;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEip(): bool
    {
        return $this->eip;
    }

    /**
     * @return bool
     */
    public function isEip(): bool
    {
        return $this->eip;
    }

    /**
     * @return bool
     */
    public function getIsUseEip(): bool
    {
        return $this->is_use_eip;
    }

    /**
     * @param bool $is_use_eip
     *
     * @return $this
     */
    public function setIsUseEip(bool $is_use_eip): Node
    {
        $this->is_use_eip = $is_use_eip;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return FrontControl
     *
     * @throws \Exception
     */
    public function addFrontControl($name): FrontControl
    {
        if (isset($this->front_controls[$name])) {
            throw new \Exception("Front control: '{$name}' already exists.");
        }

        $this->front_controls[$name] = new FrontControl();
        $this->front_controls[$name]->setDescription($this->getDescription());

        return $this->front_controls[$name];
    }

    /**
     * @return FrontControl[]
     */
    public function getFrontControls(): array
    {
        $data = [];

        if ($this->isEip() and $this->getIsUseEip()) {
            foreach ($this->front_controls as $name => $control) {
                $data[$name] = $control->getData();
            }
        }

        return $data;
    }

    /**
     * @param UserGroup $userGroup
     *
     * @return Node
     */
    public function addGroupGrantedRead(UserGroup $userGroup): Node
    {
        if (!$this->groups_granted_read->contains($userGroup)) {
            $this->groups_granted_read->add($userGroup);
        }

        return $this;
    }

    /**
     * @return Node
     */
    public function clearGroupGrantedRead(): Node
    {
        $this->groups_granted_read->clear();

        return $this;
    }

    /**
     * @return ArrayCollection|UserGroup[]
     */
    public function getGroupsGrantedRead()
    {
        return $this->groups_granted_read;
    }

    /**
     * @param ArrayCollection|UserGroup[] $groups_granted_read
     *
     * @return $this
     */
    public function setGroupsGrantedRead($groups_granted_read)
    {
        $this->groups_granted_read = $groups_granted_read;

        return $this;
    }

    /**
     * @param UserGroup $userGroup
     *
     * @return Node
     */
    public function addGroupGrantedWrite(UserGroup $userGroup): Node
    {
        if (!$this->groups_granted_write->contains($userGroup)) {
            $this->groups_granted_write->add($userGroup);
        }

        return $this;
    }

    /**
     * @return Node
     */
    public function clearGroupGrantedWrite(): Node
    {
        $this->groups_granted_write->clear();

        return $this;
    }

    /**
     * @return ArrayCollection|UserGroup[]
     */
    public function getGroupsGrantedWrite()
    {
        return $this->groups_granted_write;
    }

    /**
     * @param ArrayCollection|UserGroup[] $groups_granted_write
     *
     * @return $this
     */
    public function setGroupsGrantedWrite($groups_granted_write)
    {
        $this->groups_granted_write = $groups_granted_write;

        return $this;
    }

    /**
     * @param string|null $permission
     *
     * @return array
     */
    public function getPermissionsCache(string $permission = null): array
    {
        if (!empty($permission)) {
            if (isset($this->permissions_cache[$permission])) {
                return $this->permissions_cache[$permission];
            } else {
                return [];
            }
        }

        if (empty($this->permissions_cache)) {
            $this->permissions_cache = [];
        }

        return $this->permissions_cache;
    }

    /**
     * @param array $permissions_cache
     *
     * @return $this
     */
    public function setPermissionsCache($permissions_cache): Node
    {
        $this->permissions_cache = $permissions_cache;

        return $this;
    }

    /**
     * @return string
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @param null|string $controller
     *
     * @return Node
     */
    public function setController(?string $controller): Node
    {
        if (empty($controller)) {
            $controller = null;
        }

        $this->controller = $controller;

        return $this;
    }

    /**
     * @todo Продумать где подменять controller и action у нод.
     *
     * @return array
     *
     * @deprecated
     */
    public function getControllerTemp($controllerName = null, $actionName = 'index'): array
    {
        if (null !== $controllerName or 'index' !== $actionName) {
            $className = empty($controllerName) ? substr($this->module, 0, -12) : $controllerName;

            return [
                '_controller' => $this->module.':'.$className.':'.$actionName,
            ];
        }

        if (empty($this->controller_temp)) {
            $className = (null === $controllerName) ? substr($this->module, 0, -12) : $controllerName;
            $this->controller_temp['_controller'] = $this->module.':'.$className.':'.$actionName;
        }

        return $this->controller_temp;
    }

    /**
     * @param array $controller_temp
     *
     * @return $this
     *
     * @deprecated
     */
    public function setControllerTemp($controller_temp)
    {
        $this->controller_temp = $controller_temp;

        return $this;
    }

    /**
     * @return array
     *
     * @deprecated
     */
    public function getControllerParams(): array
    {
        $params = [];
        foreach ($this->controller_temp as $key => $val) {
            if ($key !== '_controller' and $key !== '_route') {
                $params[$key] = $val;
            }
        }

        return $params;
    }
}
