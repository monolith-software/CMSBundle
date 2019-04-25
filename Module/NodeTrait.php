<?php

namespace Monolith\Bundle\CMSBundle\Module;

use Monolith\Bundle\CMSBundle\Entity\Node;

/**
 * @deprecated
 */
trait NodeTrait
{
    /** @var  Node */
    protected $node;

    /**
     * Установка параметров ноды.
     *
     * @todo сделать проверку на доступные параметры в классе и выдавать предупреждение.
     */
    final public function setNode(Node $node)
    {
        $this->node = $node;
        foreach ($node->getParams() as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return Node
     */
    public function getNode()
    {
        return $this->node;
    }
}
