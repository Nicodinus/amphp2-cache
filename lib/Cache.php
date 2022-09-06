<?php

namespace Amp\Cache;

use Amp\Promise;

interface Cache
{
    /**
     * @param string $key
     *
     * @return Promise<bool>
     */
    public function exist(string $key): Promise;

    /**
     * @param string $key
     *
     * @return Promise<mixed|null>
     *
     * @throws CacheException
     */
    public function get(string $key): Promise;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Timeout in seconds. The default `null` $ttl value indicates no timeout. Values less than 0 applies `null` value.
     *
     * @return Promise<void>
     *
     * @throws CacheException
     */
    public function set(string $key, $value, int $ttl = null): Promise;

    /**
     * @param string $key
     *
     * @return Promise<bool|null> Resolves to `true` / `false` to indicate whether the key existed. May also resolve with `null` if that information is not available.
     *
     * @throws CacheException
     */
    public function delete(string $key): Promise;
}
