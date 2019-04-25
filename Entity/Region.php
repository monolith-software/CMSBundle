<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="cms_regions",
 *      indexes={
 *          @ORM\Index(columns={"position"}),
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="region_name_in_site", columns={"name", "site_id"}),
 *      }
 * )
 * @UniqueEntity(fields={"name", "site"}, message="Регион с таким именем уже используется")
 */
class Region
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\Description;
    use ColumnTrait\Position;
    use ColumnTrait\FosUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $permissions_cache;

    /**
     * @var Folder[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Folder", inversedBy="regions", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_regions_inherit")
     */
    protected $folders;

    /**
     * @var UserGroup[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="regions_granted_read", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_permissions_regions_read")
     * @ORM\OrderBy({"position" = "ASC", "title" = "ASC"})
     */
    protected $groups_granted_read;

    /**
     * @var UserGroup[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="regions_granted_write", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_permissions_regions_write")
     * @ORM\OrderBy({"position" = "ASC", "title" = "ASC"})
     */
    protected $groups_granted_write;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $site;

    /**
     * Region constructor.
     *
     * @param null|string $name
     * @param null|string $description
     * @param Site|null   $site
     */
    public function __construct(?string $name = null, ?string $description = null, ?Site $site = null)
    {
        $this->groups_granted_read  = new ArrayCollection();
        $this->groups_granted_write = new ArrayCollection();
        $this->permissions_cache    = [];

        $this->created_at   = new \DateTime();
        $this->folders      = new ArrayCollection();
        $this->description  = $description;
        $this->name         = $name;
        $this->position     = 0;
        $this->site         = $site;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $descr = $this->getDescription();

        return empty($descr) ? $this->getName() : $descr.' ('.$this->getName().')';
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
     * @param Folder $folder
     *
     * @return $this
     */
    public function addFolder(Folder $folder)
    {
        $this->folders->add($folder);

        return $this;
    }

    /**
     * @param Folder[] $folder
     *
     * @return $this
     */
    public function setFolders($folders)
    {
        $this->folders = $folders;

        return $this;
    }

    /**
     * @return Folder[]|ArrayCollection
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name): Region
    {
        if ('content' !== $this->name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param UserGroup $userGroup
     *
     * @return Region
     */
    public function addGroupGrantedRead(UserGroup $userGroup): Region
    {
        if (!$this->groups_granted_read->contains($userGroup)) {
            $this->groups_granted_read->add($userGroup);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|UserGroup[]
     */
    public function getGroupsGrantedRead(): Collection
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
     * @return Region
     */
    public function addGroupGrantedWrite(UserGroup $userGroup): Region
    {
        if (!$this->groups_granted_write->contains($userGroup)) {
            $this->groups_granted_write->add($userGroup);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|UserGroup[]
     */
    public function getGroupsGrantedWrite(): Collection
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
    public function setPermissionsCache($permissions_cache)
    {
        $this->permissions_cache = $permissions_cache;

        return $this;
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @param Site $site
     *
     * @return $this
     */
    public function setSite(Site $site): Region
    {
        $this->site = $site;

        return $this;
    }
}
