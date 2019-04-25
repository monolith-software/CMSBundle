<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\Group;
use Smart\CoreBundle\Doctrine\ColumnTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="users_groups",
 *      indexes={
 *          @ORM\Index(columns={"is_default_folders_granted_read"}),
 *          @ORM\Index(columns={"is_default_folders_granted_write"}),
 *          @ORM\Index(columns={"is_default_nodes_granted_read"}),
 *          @ORM\Index(columns={"is_default_nodes_granted_write"}),
 *          @ORM\Index(columns={"is_default_regions_granted_read"}),
 *          @ORM\Index(columns={"is_default_regions_granted_write"}),
 *          @ORM\Index(columns={"position"}),
 *          @ORM\Index(columns={"title"}),
 *      }
 * )
 */
class UserGroup extends Group
{
    use ColumnTrait\Position;
    use ColumnTrait\TitleNotBlank;
    use ColumnTrait\CreatedAt;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_folders_granted_read;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_folders_granted_write;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_nodes_granted_read;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_nodes_granted_write;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_regions_granted_read;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_default_regions_granted_write;

    /**
     * @var Permission[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Permission", inversedBy="user_groups", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_permissions_groups_relations")
     * @ORM\OrderBy({"bundle" = "ASC", "position" = "ASC"})
     */
    protected $permissions;

    /**
     * @var Folder[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Folder", mappedBy="groups_granted_read", fetch="EXTRA_LAZY")
     */
    protected $folders_granted_read;

    /**
     * @var Folder[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Folder", mappedBy="groups_granted_write", fetch="EXTRA_LAZY")
     */
    protected $folders_granted_write;

    /**
     * @var Node[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Node", mappedBy="groups_granted_read", fetch="EXTRA_LAZY")
     */
    protected $nodes_granted_read;

    /**
     * @var Node[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Node", mappedBy="groups_granted_write", fetch="EXTRA_LAZY")
     */
    protected $nodes_granted_write;

    /**
     * @var Region[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Region", mappedBy="groups_granted_read", fetch="EXTRA_LAZY")
     */
    protected $regions_granted_read;

    /**
     * @var Region[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Region", mappedBy="groups_granted_write", fetch="EXTRA_LAZY")
     */
    protected $regions_granted_write;

    /**
     * UserGroup constructor.
     *
     * @param string $name
     * @param array  $roles
     */
    public function __construct(string $name, array $roles = [])
    {
        $this->folders_granted_read  = new ArrayCollection();
        $this->folders_granted_write = new ArrayCollection();
        $this->nodes_granted_read    = new ArrayCollection();
        $this->nodes_granted_write   = new ArrayCollection();
        $this->regions_granted_read  = new ArrayCollection();
        $this->regions_granted_write = new ArrayCollection();
        $this->is_default_folders_granted_read  = true;
        $this->is_default_folders_granted_write = true;
        $this->is_default_nodes_granted_read    = true;
        $this->is_default_nodes_granted_write   = true;
        $this->is_default_regions_granted_read  = true;
        $this->is_default_regions_granted_write = true;

        $this->created_at            = new \DateTime();
        $this->permissions           = new ArrayCollection();
        $this->position              = 0;

        parent::__construct($name, $roles);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getTitle();
    }

    /**
     * @param Permission $permission
     *
     * @return $this
     */
    public function addPermission(Permission $permission): UserGroup
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }
    
    /**
     * @return Collection|Permission[]
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    /**
     * @param ArrayCollection|Permission[] $permissions
     *
     * @return $this
     */
    public function setPermissions($permissions): UserGroup
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return ArrayCollection|Folder[]
     */
    public function getFoldersGrantedRead()
    {
        return $this->folders_granted_read;
    }

    /**
     * @param ArrayCollection|Folder[] $folders_granted_read
     *
     * @return $this
     */
    public function setFoldersGrantedRead($folders_granted_read)
    {
        $this->folders_granted_read = $folders_granted_read;

        return $this;
    }

    /**
     * @return ArrayCollection|Folder[]
     */
    public function getFoldersGrantedWrite()
    {
        return $this->folders_granted_write;
    }

    /**
     * @param ArrayCollection|Folder[] $folders_granted_write
     *
     * @return $this
     */
    public function setFoldersGrantedWrite($folders_granted_write)
    {
        $this->folders_granted_write = $folders_granted_write;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultFoldersGrantedRead(): bool
    {
        return $this->is_default_folders_granted_read;
    }

    /**
     * @param bool $is_default_folders_granted_read
     *
     * @return $this
     */
    public function setIsDefaultFoldersGrantedRead($is_default_folders_granted_read)
    {
        $this->is_default_folders_granted_read = $is_default_folders_granted_read;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultFoldersGrantedWrite(): bool
    {
        return $this->is_default_folders_granted_write;
    }

    /**
     * @param bool $is_default_folders_granted_write
     *
     * @return $this
     */
    public function setIsDefaultFoldersGrantedWrite($is_default_folders_granted_write)
    {
        $this->is_default_folders_granted_write = $is_default_folders_granted_write;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultNodesGrantedRead(): bool
    {
        return $this->is_default_nodes_granted_read;
    }

    /**
     * @param bool $is_default_nodes_granted_read
     *
     * @return $this
     */
    public function setIsDefaultNodesGrantedRead($is_default_nodes_granted_read)
    {
        $this->is_default_nodes_granted_read = $is_default_nodes_granted_read;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultNodesGrantedWrite(): bool
    {
        return $this->is_default_nodes_granted_write;
    }

    /**
     * @param bool $is_default_nodes_granted_write
     *
     * @return $this
     */
    public function setIsDefaultNodesGrantedWrite($is_default_nodes_granted_write)
    {
        $this->is_default_nodes_granted_write = $is_default_nodes_granted_write;

        return $this;
    }

    /**
     * @return ArrayCollection|Node[]
     */
    public function getNodesGrantedRead()
    {
        return $this->nodes_granted_read;
    }

    /**
     * @param ArrayCollection|Node[] $nodes_granted_read
     *
     * @return $this
     */
    public function setNodesGrantedRead($nodes_granted_read)
    {
        $this->nodes_granted_read = $nodes_granted_read;

        return $this;
    }

    /**
     * @return ArrayCollection|Node[]
     */
    public function getNodesGrantedWrite()
    {
        return $this->nodes_granted_write;
    }

    /**
     * @param ArrayCollection|Node[] $nodes_granted_write
     *
     * @return $this
     */
    public function setNodesGrantedWrite($nodes_granted_write)
    {
        $this->nodes_granted_write = $nodes_granted_write;

        return $this;
    }

    /**
     * @return ArrayCollection|Region[]
     */
    public function getRegionsGrantedRead()
    {
        return $this->regions_granted_read;
    }

    /**
     * @param ArrayCollection|Region[] $regions_granted_read
     *
     * @return $this
     */
    public function setRegionsGrantedRead($regions_granted_read)
    {
        $this->regions_granted_read = $regions_granted_read;

        return $this;
    }

    /**
     * @return ArrayCollection|Region[]
     */
    public function getRegionsGrantedWrite()
    {
        return $this->regions_granted_write;
    }

    /**
     * @param ArrayCollection|Region[] $regions_granted_write
     *
     * @return $this
     */
    public function setRegionsGrantedWrite($regions_granted_write)
    {
        $this->regions_granted_write = $regions_granted_write;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultRegionsGrantedRead(): bool
    {
        return $this->is_default_regions_granted_read;
    }

    /**
     * @param bool $is_default_regions_granted_read
     *
     * @return $this
     */
    public function setIsDefaultRegionsGrantedRead($is_default_regions_granted_read)
    {
        $this->is_default_regions_granted_read = $is_default_regions_granted_read;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultRegionsGrantedWrite(): bool
    {
        return $this->is_default_regions_granted_write;
    }

    /**
     * @param bool $is_default_regions_granted_write
     *
     * @return $this
     */
    public function setIsDefaultRegionsGrantedWrite($is_default_regions_granted_write)
    {
        $this->is_default_regions_granted_write = $is_default_regions_granted_write;

        return $this;
    }
}
