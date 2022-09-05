<?php

namespace Amp\Cache;

use Amp\ByteStream\InputStream;
use Amp\Iterator;
use Amp\Promise;

interface Cache
{
    /**
     * Gets a value associated with the given key.
     *
     * If the specified key doesn't exist implementations MUST succeed the resulting promise with `null`.
     *
     * @param $key string Cache key.
     *
     * @return Promise<mixed|null> Resolves to the cached value nor `null` if it doesn't exist or fails with a
     * CacheException on failure.
     */
    public function get(string $key): Promise;

    /**
     * @param string $key
     *
     * @return Iterator<mixed>
     */
    public function getIterator(string $key): Iterator;

    /**
     * @param string $key
     *
     * @return InputStream
     *
     * @see Cache::get()
     */
    public function getStream(string $key): InputStream;

    /**
     * Sets a value associated with the given key. Overrides existing values (if they exist).
     *
     * The eventual resolution value of the resulting promise is unimportant. The success or failure of the promise
     * indicates the operation's success.
     *
     * @param string $key Cache key.
     * @param mixed $value Value to cache.
     * @param int|null $ttl Timeout in seconds. The default `null` $ttl value indicates no timeout. Values less than 0 MUST
     * throw an \Error.
     *
     * @return Promise<void> Resolves either successfully or fails with a CacheException on failure.
     *
     * @throws CacheException On failure to store the cached value
     *
     * @psalm-param mixed $value
     */
    public function set(string $key, $value, int $ttl = null): Promise;

    /**
     * @param string $key
     * @param Iterator<mixed> $iterator
     * @param int|null $ttl
     *
     * @return Promise<void>
     *
     * @see Cache::set()
     */
    public function setIterator(string $key, Iterator $iterator, int $ttl = null): Promise;

    /**
     * @param string $key
     * @param InputStream $stream
     * @param int|null $ttl
     *
     * @return Promise<void>
     *
     * @see Cache::set()
     */
    public function setStream(string $key, InputStream $stream, int $ttl = null): Promise;

    /**
     * Deletes a value associated with the given key if it exists.
     *
     * Implementations SHOULD return boolean `true` or `false` to indicate whether the specified key existed at the time
     * the delete operation was requested. If such information is not available, the implementation MUST resolve the
     * promise with `null`.
     *
     * Implementations MUST transparently succeed operations for non-existent keys.
     *
     * @param $key string Cache key.
     *
     * @return Promise<bool|null> Resolves to `true` / `false` to indicate whether the key existed or fails with a
     * CacheException on failure. May also resolve with `null` if that information is not available.
     */
    public function delete(string $key): Promise;
}
