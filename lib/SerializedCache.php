<?php

namespace Amp\Cache;

use Amp\Promise;
use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use function Amp\call;

final class SerializedCache implements Cache
{
    /** @var Cache */
    private Cache $cache;

    /** @var Serializer */
    private Serializer $serializer;

    //

    /**
     * @param Cache $cache
     * @param Serializer $serializer
     */
    public function __construct(Cache $cache, Serializer $serializer)
    {
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function exist(string $key): Promise
    {
        return $this->cache->exist($key);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): Promise
    {
        return call(function () use ($key) {
            $data = yield $this->cache->get($key);
            if ($data === null) {
                return null;
            }

            return $this->serializer->unserialize($data);
        });
    }

    /**
     * @inheritDoc
     *
     * @throws SerializationException
     */
    public function set(string $key, $value, int $ttl = null): Promise
    {
        if ($value === null) {
            throw new CacheException('Cannot store NULL in ' . self::class);
        }

        $value = $this->serializer->serialize($value);
        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return $this->cache->delete($key);
    }
}
