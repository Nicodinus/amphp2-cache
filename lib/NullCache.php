<?php

namespace Amp\Cache;

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\InputStream;
use Amp\Cache\Internal\CompletedIterator;
use Amp\Iterator;
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
    public function get(string $key): Promise
    {
        return new Success;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(string $key): Iterator
    {
        return CompletedIterator::complete();
    }

    /**
     * @inheritDoc
     */
    public function getStream(string $key): InputStream
    {
        return new InMemoryStream();
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
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function setIterator(string $key, Iterator $iterator, int $ttl = null): Promise
    {
        return new Success;
    }

    /**
     * @inheritDoc
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function setStream(string $key, InputStream $stream, int $ttl = null): Promise
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
