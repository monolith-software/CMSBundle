<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_appearance_history",
 *      indexes={
 *          @ORM\Index(columns={"hash"}),
 *          @ORM\Index(columns={"path"}),
 *          @ORM\Index(columns={"filename"}),
 *      }
 * )
 */
class AppearanceHistory
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\FosUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $filename;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    protected $hash;

    /**
     * AppearanceHistory constructor.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode(string $code): AppearanceHistory
    {
        $this->code = $code;
        $this->hash = md5($code);

        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename(string $filename): AppearanceHistory
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): AppearanceHistory
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash(string $hash): AppearanceHistory
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
