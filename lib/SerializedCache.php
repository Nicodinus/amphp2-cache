<?php

namespace Amp\Cache;

use Amp\ByteStream\InputStream;
use Amp\Emitter;
use Amp\Iterator;
use Amp\Promise;
use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use function Amp\asyncCall;
use function Amp\call;

/**
 * @template TValue
 */
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
     * Fetch a value from the cache and unserialize it.
     *
     * @param $key string Cache key.
     *
     * @return Promise<mixed|null> Resolves to the cached value or `null` if it doesn't exist. Fails with a
     * CacheException or SerializationException on failure.
     *
     * @psalm-return Promise<TValue|null>
     *
     * @see Cache::get()
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
     */
    public function getIterator(string $key): Iterator
    {
        if (\is_callable([$this->serializer, 'unserializeIterator'])) {
            return $this->serializer->unserializeIterator($this->cache->getIterator($key));
        }

        $emitter = new Emitter();

        asyncCall(function () use (&$key, &$emitter) {
            try {
                $iterator = $this->cache->getIterator($key);

                while (true === yield $iterator->advance()) {
                    if ($iterator->getCurrent() === null) {
                        continue;
                    }

                    yield $emitter->emit($this->serializer->unserialize($iterator->getCurrent()));
                }

                $emitter->complete();
            } catch (\Throwable $exception) {
                $emitter->fail($exception);
            }
        });

        return $emitter->iterate();
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $key): InputStream
    {
        if (\is_callable([$this->serializer, 'unserializeStream'])) {
            return $this->serializer->unserializeStream($this->cache->getStream($key));
        }

        throw new \BadMethodCallException("Can't unserialize data from stream, there is no realization for unserializing stream!");
    }

    /**
     * Serializes a value and stores its serialization to the cache.
     *
     * @param $key   string Cache key.
     * @param $value mixed Value to cache.
     * @param $ttl   int|null Timeout in seconds. The default `null` $ttl value indicates no timeout. Values less than 0 MUST
     *               throw an \Error.
     *
     * @psalm-param TValue $value
     *
     * @return Promise<void> Resolves either successfully or fails with a CacheException or SerializationException.
     *
     * @throws CacheException On failure to store the cached value
     * @throws SerializationException
     *
     * @see Cache::set()
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
    public function setIterator(string $key, Iterator $iterator, int $ttl = null): Promise
    {
        return call(function () use (&$key, &$iterator, &$ttl) {
            $emitter = new Emitter();
            $promise = $this->cache->setIterator($key, $emitter->iterate(), $ttl);

            try {
                while (true === yield $iterator->advance()) {
                    if ($iterator->getCurrent() === null) {
                        continue;
                    }

                    $value = $this->serializer->serialize($iterator->getCurrent());
                    yield $emitter->emit($value);
                }

                $emitter->complete();
            } catch (\Throwable $exception) {
                $emitter->fail($exception);
                throw $exception;
            }

            return $promise;
        });
    }

    /**
     * @inheritDoc
     */
    public function setStream(string $key, InputStream $stream, int $ttl = null): Promise
    {
        if (\is_callable([$this->serializer, 'serializeStream'])) {
            return $this->setStream($key, $this->serializer->serializeStream($stream), $ttl);
        }

        throw new \BadMethodCallException("Can't serialize data from stream, there is no realization for serializing stream!");
    }

    /**
     * Deletes a value associated with the given key if it exists.
     *
     * @param $key string Cache key.
     *
     * @return Promise<bool|null> Resolves to `true` / `false` to indicate whether the key existed or fails with a
     * CacheException on failure. May also resolve with `null` if that information is not available.
     *
     * @see Cache::delete()
     */
    public function delete(string $key): Promise
    {
        return $this->cache->delete($key);
    }
}
