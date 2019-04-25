<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Cache;

use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

class CacheWrapper
{
    /**
     * @var TaggableCacheItemPoolInterface
     */
    protected $provider;

    /**
     * CacheWrapper constructor.
     *
     * @param TaggableCacheItemPoolInterface $provider
     */
    public function __construct(TaggableCacheItemPoolInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return TaggableCacheItemPoolInterface
     */
    public function getProvider(): TaggableCacheItemPoolInterface
    {
        return $this->provider;
    }

    /**
     * Save cache data.
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $tags
     * @param int|\DateInterval|null  $ttl
     */
    public function set(string $key, $value, array $tags = [], $ttl = null)
    {
        $item = $this->provider->getItem($key);
        $item->set($value);

        if (!empty($tags)) {
            $item->setTags($tags);
        }

        if (!empty($ttl)) {
            $item->expiresAfter($ttl);
        }

        $this->provider->save($item);
    }

    /**
     * Get cache item value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->provider->getItem($key)->get();
    }

    /**
     * @param string|array $keys
     *
     * @return bool
     */
    public function delete($keys): bool
    {
        if (is_array($keys)) {
            return $this->provider->deleteItems($keys);
        }

        return $this->provider->deleteItem($keys);
    }

    /**
     * @param string $key
     *
     * @return TaggableCacheItemInterface
     */
    public function getItem(string $key): TaggableCacheItemInterface
    {
        return $this->provider->getItem($key);
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function getItemTags(string $key): array
    {
        return $this->provider->getItem($key)->getPreviousTags();
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function invalidateTag(string $tag): bool
    {
        return $this->provider->invalidateTag($tag);
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        return $this->provider->invalidateTags($tags);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->provider->clear();
    }
}
