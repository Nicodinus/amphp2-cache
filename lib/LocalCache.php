<?php

namespace Amp\Cache;

use Amp\Loop;
use Amp\Promise;
use Amp\Struct;
use Amp\Success;

final class LocalCache implements Cache
{
    /** @var object */
    private object $sharedState;

    /** @var string|null */
    private ?string $ttlWatcherId;

    /** @var int|null */
    private ?int $maxSize;

    /**
     * @param int $gcInterval The frequency in milliseconds at which expired cache entries should be garbage collected.
     * @param int|null $maxSize The maximum size of cache array (number of elements).
     */
    public function __construct(int $gcInterval = 5000, int $maxSize = null)
    {
        if ($maxSize !== null && $maxSize < 1) {
            throw new \Error("Invalid cache max size ({$maxSize}; integer >= 0 or null required");
        }
        $this->maxSize = $maxSize;

        if ($gcInterval < 1) {
            $gcInterval = 5000;
        }

        // By using a shared state object we're able to use `__destruct()` for "normal" garbage collection of both this
        // instance and the loop's watcher. Otherwise this object could only be GC'd when the TTL watcher was cancelled
        // at the loop layer.
        $this->sharedState = new class {
            use Struct;

            /** @var string[] */
            public array $cache = [];
            /** @var int[] */
            public array $cacheTimeouts = [];
            /** @var bool */
            public bool $isSortNeeded = false;

            /**
             * @return void
             */
            public function collectGarbage(): void
            {
                $now = \hrtime(true);

                if ($this->isSortNeeded) {
                    \asort($this->cacheTimeouts);
                    $this->isSortNeeded = false;
                }

                foreach ($this->cacheTimeouts as $key => $expiry) {
                    if ($now <= $expiry) {
                        break;
                    }

                    unset(
                        $this->cache[$key],
                        $this->cacheTimeouts[$key]
                    );
                }
            }
        };

        $this->ttlWatcherId = Loop::repeat($gcInterval, \Closure::fromCallable([$this->sharedState, "collectGarbage"]));
        Loop::unreference($this->ttlWatcherId);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->sharedState->cache = [];
        $this->sharedState->cacheTimeouts = [];

        if (!empty($this->ttlWatcherId)) {
            Loop::cancel($this->ttlWatcherId);
            $this->ttlWatcherId = null;
        }
    }

    /** @inheritdoc */
    public function get(string $key): Promise
    {
        if (!isset($this->sharedState->cache[$key])) {
            return new Success;
        }

        if (isset($this->sharedState->cacheTimeouts[$key]) && \hrtime(true) > $this->sharedState->cacheTimeouts[$key]) {
            unset(
                $this->sharedState->cache[$key],
                $this->sharedState->cacheTimeouts[$key]
            );

            return new Success;
        }

        return new Success($this->sharedState->cache[$key]);
    }

    /**
     * @inheritDoc
     * @psalm-param mixed $value
     * @return Promise<void>
     */
    public function set(string $key, $value, int $ttl = null): Promise
    {
        if ($value === null) {
            throw new CacheException('Cannot store NULL in ' . self::class);
        }

        if ($ttl === null) {
            unset($this->sharedState->cacheTimeouts[$key]);
        } elseif ($ttl >= 0) {
            $expiry = \hrtime(true) + $ttl * 1e+9;
            $this->sharedState->cacheTimeouts[$key] = $expiry;
            $this->sharedState->isSortNeeded = true;
        } else {
            throw new \Error("Invalid cache TTL ({$ttl}; integer >= 0 or null required");
        }

        if ($this->maxSize !== null) {
            unset($this->sharedState->cache[$key]);
            while (\sizeof($this->sharedState->cache) > $this->maxSize) {
                \array_shift($this->sharedState->cache);
            }
        }
        $this->sharedState->cache[$key] = $value;

        /** @var Promise<void> */
        return new Success;
    }

    /** @inheritdoc */
    public function delete(string $key): Promise
    {
        $exists = isset($this->sharedState->cache[$key]);

        unset(
            $this->sharedState->cache[$key],
            $this->sharedState->cacheTimeouts[$key]
        );

        return new Success($exists);
    }
}
