<?php

namespace Amp\Cache;

use Amp\ByteStream\InputStream;
use Amp\Iterator;
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
    public function get(string $key): Promise
    {
        return $this->cache->get($this->keyPrefix . $key);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(string $key): Iterator
    {
        return $this->cache->getIterator($this->keyPrefix . $key);
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $key): InputStream
    {
        return $this->cache->getStream($this->keyPrefix . $key);
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
    public function setIterator(string $key, Iterator $iterator, int $ttl = null): Promise
    {
        return $this->cache->setIterator($this->keyPrefix . $key, $iterator, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function setStream(string $key, InputStream $stream, int $ttl = null): Promise
    {
        return $this->cache->setStream($this->keyPrefix . $key, $stream, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return $this->cache->delete($this->keyPrefix . $key);
    }
}
