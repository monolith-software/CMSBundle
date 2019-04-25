<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Tools;

class FrontControl
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $description;

    /** @var string */
    protected $uri;

    /** @var bool */
    protected $isDefault;

    /**
     * FrontControl constructor.
     */
    public function __construct()
    {
        $this->isDefault = true;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(?string $description): FrontControl
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param bool $isDefault
     *
     * @return $this
     */
    public function setIsDefault(bool $isDefault): FrontControl
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): FrontControl
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param string $uri
     *
     * @return $this
     */
    public function setUri(string $uri): FrontControl
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return [
            'title'         => $this->title,
            'description'   => $this->description,
            'is_default'    => $this->isDefault,
            'uri'           => $this->uri,
        ];
    }
}
