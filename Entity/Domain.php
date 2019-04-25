<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_domains")
 *
 * @UniqueEntity(fields="name", message="Данный домен занят")
 *
 * @todo user
 */
class Domain
{
    use ColumnTrait\Id;
    use ColumnTrait\NameUnique;
    use ColumnTrait\Comment;
    use ColumnTrait\IsEnabled;
    use ColumnTrait\Position;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\UpdatedAt;
    use ColumnTrait\FosUser;

    /**
     * For Aliases
     *
     * @var Domain|null
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="children")
     * @ORM\JoinColumn(name="parent_pid")
     */
    protected $parent;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_redirect;

    /**
     * List of aliases
     *
     * @var Domain[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="parent")
     * @ORM\OrderBy({"position" = "ASC", "name" = "ASC"})
     */
    protected $children;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $paid_till_date;

    /**
     * @var Language|null
     *
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="domains")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $language;

    /**
     * Domain constructor.
     *
     * @param null|string $name
     */
    public function __construct(?string $name = null)
    {
        if (!empty($name)) {
            $this->name = $name;
        }

        $this->children   = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->is_enabled = true;
        $this->is_redirect = false;
        $this->position   = 0;
    }

    /**
     * @return \DateTime|null
     */
    public function getPaidTillDate(): ?\DateTime
    {
        return $this->paid_till_date;
    }

    /**
     * @param \DateTime $paid_till_date
     *
     * @return $this
     */
    public function setPaidTillDate(?\DateTime $paid_till_date): Domain
    {
        $this->paid_till_date = $paid_till_date;

        return $this;
    }

    /**
     * @return Domain|null
     */
    public function getParent(): ?Domain
    {
        return $this->parent;
    }

    /**
     * @param Domain|null $parent
     *
     * @return $this
     */
    public function setParent(?Domain $parent): Domain
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return ArrayCollection|Domain[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return bool
     */
    public function isRedirect(): bool
    {
        return (bool) $this->is_redirect;
    }

    /**
     * @param bool $is_redirect
     *
     * @return $this
     */
    public function setIsRedirect($is_redirect): Domain
    {
        $this->is_redirect = (bool) $is_redirect;

        return $this;
    }

    /**
     * @return Language|null
     */
    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    /**
     * @param Language|null $language
     *
     * @return $this
     */
    public function setLanguage(?Language $language): Domain
    {
        $this->language = $language;

        return $this;
    }
}
