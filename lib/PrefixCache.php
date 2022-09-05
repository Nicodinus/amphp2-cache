<?php

namespace Amp\Cache;

use Amp\Promise;

final class PrefixCache implements Cache
{
    /** @var Cache */
    private Cache $cache;

    /** @var string */
    private string $keyPrefix;

    //

    /**
     * @param Cache $cache
     * @param string $keyPrefix
     */
    public function __construct(Cache $cache, string $keyPrefix)
    {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Gets the specified key prefix.
     *
     * @return string
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * @inheritDoc
     */
    public function exist(string $key): Promise
    {
        return $this->cache->exist($this->keyPrefix . $key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): Promise
    {
        return $this->cache->get($this->keyPrefix . $key);
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $key): Promise
    {
        return $this->cache->getItem($this->keyPrefix . $key);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, int $ttl = null): Promise
    {
        return $this->cache->set($this->keyPrefix . $key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return $this->cache->delete($this->keyPrefix . $key);
    }
}
