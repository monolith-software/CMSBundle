<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_modules",
 *      indexes={
 *          @ORM\Index(columns={"is_active"})
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"name", "developer"})
 *      }
 * )
 *
 * @UniqueEntity(fields={"bundle"}, message="Module this this bundle name is already installed.")
 */
class Module
{
    use ColumnTrait\Id;
    use ColumnTrait\IsActive;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\FosUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $developer;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    protected $bundle;

    /**
     * @var array
     */
    protected $info;

    /**
     * Module constructor.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @see getName
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): Module
    {
        $this->name = trim($name);

        return $this;
    }

    /**
     * @return string
     */
    public function getDeveloper(): string
    {
        return $this->developer;
    }

    /**
     * @param string $developer
     *
     * @return $this
     */
    public function setDeveloper($developer)
    {
        $this->developer = $developer;

        return $this;
    }

    /**
     * @param string|null $key
     *
     * @return array
     */
    public function getInfo(string $key = null): array
    {
        if ($key) {
            return $this->info[$key];
        }

        return $this->info;
    }

    /**
     * @param array $info
     *
     * @return $this
     */
    public function setInfo(array $info): Module
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $val
     *
     * @return Module
     */
    public function addInfo(string $key, $val): Module
    {
        $this->info[$key] = $val;

        return $this;
    }
}
