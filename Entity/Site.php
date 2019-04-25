<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_sites")
 *
 * @UniqueEntity(fields={"name"}, message="Сайт с таким именем уже существует.")
 */
class Site
{
    use ColumnTrait\Id;
    use ColumnTrait\IsEnabled;
    use ColumnTrait\NameUnique;
    use ColumnTrait\Position;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\UpdatedAt;
    use ColumnTrait\FosUser;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $theme;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $web_root;

    /**
     * @var Domain|null
     *
     * @ORM\OneToOne(targetEntity="Domain")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $domain;

    /**
     * @var Folder|null
     *
     * @ORM\OneToOne(targetEntity="Folder")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $root_folder;

    /**
     * @var Language
     *
     * @ORM\ManyToOne(targetEntity="Language")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $language;

    /**
     * Site constructor.
     *
     * @param null|string $name
     */
    public function __construct(?string $name = null)
    {
        if (!empty($name)) {
            $this->name = $name;
        }

        $this->created_at = new \DateTime();
        $this->is_enabled = true;
        $this->position   = 0;
    }

    /**
     * @return null|Domain
     */
    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    /**
     * @param Domain|null $domain
     *
     * @return $this
     */
    public function setDomain(?Domain $domain): ?Site
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getWebRoot()
    {
        return $this->web_root;
    }

    /**
     * @param null|string $web_root
     *
     * @return $this
     */
    public function setWebRoot($web_root)
    {
        $this->web_root = $web_root;

        return $this;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->language;
    }

    /**
     * @param Language $language
     *
     * @return $this
     */
    public function setLanguage(Language $language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return Folder|null
     */
    public function getRootFolder(): ?Folder
    {
        return $this->root_folder;
    }

    /**
     * @param Folder|null $root_folder
     *
     * @return $this
     */
    public function setRootFolder(?Folder $root_folder): Site
    {
        $this->root_folder = $root_folder;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }

    /**
     * @param null|string $theme
     *
     * @return $this
     */
    public function setTheme(?string $theme): Site
    {
        $this->theme = $theme;

        return $this;
    }
}
