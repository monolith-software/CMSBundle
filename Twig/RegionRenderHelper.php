<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Twig;

/**
 * @deprecated переделать на twig функцию {{ cms_region('container') }}
 */
class RegionRenderHelper
{
    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        foreach ($this as $_dummy_nodeId => $response) {
            echo $response->getContent();
        }

        return '';
    }

    public function count(): int
    {
        $cntNodes = 0;
        foreach ($this as $_dummy_nodeId => $response) {
            $cntNodes++;
        }

        return $cntNodes;
    }
}
