<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_permissions",
 *      indexes={
 *          @ORM\Index(columns={"position"}),
 *          @ORM\Index(columns={"default_value"}),
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"bundle", "action"}),
 *      }
 * )
 */
class Permission
{
    use ColumnTrait\Id;
    use ColumnTrait\Position;
    use ColumnTrait\CreatedAt;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=false)
     * @Assert\NotBlank()
     */
    protected $bundle;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=false)
     * @Assert\NotBlank()
     */
    protected $action;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $default_value;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @var UserGroup[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", mappedBy="permissions", fetch="EXTRA_LAZY")
     */
    protected $user_groups;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->default      = false;
        $this->roles        = [];
        $this->created_at   = new \DateTime();
        $this->user_groups  = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getBundle().':'.$this->getAction();
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getBundle(): string
    {
        return $this->bundle;
    }

    /**
     * @param string $bundle
     *
     * @return $this
     */
    public function setBundle(string $bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return Collection|UserGroup[]
     */
    public function getUserGroups(): Collection
    {
        return $this->user_groups;
    }

    /**
     * @param ArrayCollection|UserGroup[] $user_groups
     *
     * @return $this
     */
    public function setUserGroups($user_groups)
    {
        $this->user_groups = $user_groups;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultValue(): bool
    {
        return $this->default_value;
    }

    /**
     * @param bool $default_value
     *
     * @return $this
     */
    public function setDefaultValue($default_value): Permission
    {
        $this->default_value = $default_value;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }
}
