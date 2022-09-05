<?php

namespace Amp\Cache;

use Amp\ByteStream\InputStream;
use Amp\Coroutine;
use Amp\Emitter;
use Amp\Iterator;
use Amp\Promise;
use Amp\Serialization\Serializer;
use function Amp\asyncCall;
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
     */
    public function getItem(string $key): Promise
    {
        return call(function () use (&$key) {
            /** @var CacheItem|null $data */
            $data = yield $this->cache->getItem($key);
            if ($data === null) {
                return null;
            }

            if ($data->isIterable()) {
                $emitter = new Emitter();

                asyncCall(function () use (&$data, &$emitter) {
                    try {
                        $iterator = $data->getIterator();

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

                return new CacheItem($emitter->iterate());
            } elseif ($data->isStream()) {
                if (\is_callable([$this->serializer, 'unserializeStream'])) {
                    return new CacheItem($this->serializer->unserializeStream($data->getStream()));
                }

                throw new \BadMethodCallException("Can't unserialize data from stream, there is no realization for unserializing stream!");
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, int $ttl = null): Promise
    {
        if ($value === null) {
            throw new CacheException('Cannot store NULL in ' . self::class);
        }

        return call(function () use (&$key, &$value, &$ttl) {
            if (\is_callable($value)) {
                $value = call($value);
            } elseif ($value instanceof \Generator) {
                $value = new Coroutine($value);
            }

            if ($value instanceof Promise) {
                $value = yield $value;
            }

            if ($value instanceof CacheItem) {
                $value = $value->getResult();
            }

            if ($value instanceof Iterator) {
                $emitter = new Emitter();

                asyncCall(function () use (&$value, &$emitter) {
                    try {
                        while (true === yield $value->advance()) {
                            if ($value->getCurrent() === null) {
                                continue;
                            }

                            yield $emitter->emit($this->serializer->serialize($value->getCurrent()));
                        }

                        $emitter->complete();
                    } catch (\Throwable $exception) {
                        $emitter->fail($exception);
                    }
                });

                $value = $emitter->iterate();
            } elseif ($value instanceof InputStream) {
                if (!\is_callable([$this->serializer, 'serializeStream'])) {
                    throw new \BadMethodCallException("Can't serialize data from stream, there is no realization for serializing stream!");
                }

                $value = $this->serializer->serializeStream($value);
            } else {
                $value = $this->serializer->serialize($value);
            }

            $this->cache->set($key, new CacheItem($value), $ttl);
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return $this->cache->delete($key);
    }
}
