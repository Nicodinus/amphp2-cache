<?php

namespace Amp\Cache;

use Amp\Promise;
use Amp\Success;

/**
 * Cache implementation that just ignores all operations and always resolves to `null`.
 */
final class NullCache implements Cache
{
    /**
     * @inheritDoc
     */
    public function exist(string $key): Promise
    {
        return new Success(false);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): Promise
    {
        return new Success;
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $key): Promise
    {
        return new Success;
    }

    /**
     * @inheritDoc
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function set(string $key, $value, int $ttl = null): Promise
    {
        return new Success;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): Promise
    {
        return new Success(false);
    }
}
