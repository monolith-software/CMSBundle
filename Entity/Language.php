<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_languages",
 *      indexes={
 *          @ORM\Index(columns={"is_enabled"}),
 *          @ORM\Index(columns={"position"})
 *      }
 * )
 *
 * @UniqueEntity(fields={"name"}, message="Язык с таким именем уже существует.")
 * @UniqueEntity(fields={"code"}, message="Язык с таким кодом уже существует.")
 */
class Language
{
    use ColumnTrait\Id;
    use ColumnTrait\IsEnabled;
    use ColumnTrait\NameUnique;
    use ColumnTrait\Position;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\UpdatedAt;
    use ColumnTrait\FosUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=12)
     */
    protected $code;

    /**
     * @var Domain[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="language")
     */
    protected $domains;

    /**
     * Language constructor.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->code       = '';
        $this->domains    = new ArrayCollection();
        $this->is_enabled = true;
        $this->position   = 0;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     *
     * @return $this
     */
    public function setCode(?string $code): Language
    {
        $this->code = (string) $code;

        return $this;
    }

    /**
     * @return ArrayCollection|Domain[]
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param ArrayCollection|Domain[] $domains
     *
     * @return $this
     */
    public function setDomains($domains)
    {
        $this->domains = $domains;

        return $this;
    }
}
