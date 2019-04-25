<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Model;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;

/**
 * ORM\Entity
 * ORM\Table(name="users")
 */
class UserModel extends BaseUser
{
    use ColumnTrait\CreatedAt;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $firstname;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $lastname;

    /**
     * @ORM\ManyToMany(targetEntity="Monolith\Bundle\CMSBundle\Entity\UserGroup")
     * @ORM\JoinTable(name="users_groups_relations",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

    /**
     * UserModel constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->created_at = new \DateTime();
    }

    /**
     * @return array
     */
    public function getGroupNames(): array
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[$group->getId()] = $group->getName();
        }

        return $names;
    }

    /**
     * @return string
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param null|string $firstname
     *
     * @return UserModel
     */
    public function setFirstname(?string $firstname): UserModel
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param null|string $lastname
     *
     * @return UserModel
     */
    public function setLastname(?string $lastname): UserModel
    {
        $this->lastname = $lastname;
    }

    /**
     * Get the full name of the user (first + last name).
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->getFirstName().' '.$this->getLastname();
    }
}
