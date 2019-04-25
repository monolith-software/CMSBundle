<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Module;

use Monolith\Bundle\CMSBundle\Cache\CacheWrapper;

trait CacheTrait
{
    /**
     * @return CacheWrapper
     */
    protected function getCacheService(): CacheWrapper
    {
        return $this->get('cms.cache');
    }
}
