<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;

/**
 * @ORM\Entity(repositoryClass="Monolith\Bundle\CMSBundle\Repository\SyslogRepository")
 * @ORM\Table(name="cms_syslog",
 *      indexes={
 *          @ORM\Index(columns={"created_at"}),
 *          @ORM\Index(columns={"bundle"}),
 *          @ORM\Index(columns={"entity"})
 *      }
 * )
 */
class Syslog
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\FosUser;
    use ColumnTrait\IpAddress;

    /**
     * create, update, delete.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $action;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $bundle;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $entity_id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $entity;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $domain;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $old_value;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $new_value;

    /**
     * Syslog constructor.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->entity_id  = 0;
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
    public function setAction(string $action): Syslog
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     *
     * @return $this
     */
    public function setEntity(string $entity): Syslog
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entity_id;
    }

    /**
     * @param int $entity_id
     *
     * @return $this
     */
    public function setEntityId(int $entity_id): Syslog
    {
        $this->entity_id = $entity_id;

        return $this;
    }

    /**
     * @return array
     */
    public function getOldValue(): array
    {
        return $this->old_value;
    }

    /**
     * @param array $old_value
     *
     * @return $this
     */
    public function setOldValue(array $old_value): Syslog
    {
        $this->old_value = $old_value;

        return $this;
    }

    /**
     * @return array
     */
    public function getNewValue(): array
    {
        return $this->new_value;
    }

    /**
     * @param array $new_value
     *
     * @return $this
     */
    public function setNewValue(array $new_value): Syslog
    {
        $this->new_value = $new_value;

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
    public function setBundle(string $bundle): Syslog
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * @param null|string $domain
     *
     * @return $this
     */
    public function setDomain(?string $domain): Syslog
    {
        $this->domain = $domain;

        return $this;
    }
}
