<?php

namespace Amp\Cache;

use Amp\ByteStream\InputStream;
use Amp\Iterator;

class CacheItem implements \Stringable
{
    /** @var mixed|Iterator<mixed>|InputStream */
    private $result;

    //

    /**
     * @param mixed|Iterator<mixed>|InputStream $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function isIterable(): bool
    {
        return $this->result instanceof Iterator;
    }

    /**
     * @return bool
     */
    public function isStream(): bool
    {
        return $this->result instanceof InputStream;
    }

    /**
     * @return mixed|InputStream|Iterator
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return Iterator<mixed>
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function getIterator(): Iterator
    {
        if (!$this->isIterable()) {
            throw new \BadMethodCallException;
        }

        return $this->result;
    }

    /**
     * @return InputStream
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function getStream(): InputStream
    {
        if (!$this->isStream()) {
            throw new \BadMethodCallException;
        }

        return $this->result;
    }

    /**
     * @inheritDoc
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function __toString()
    {
        if (!$this->isStream() && !$this->isIterable()) {
            return $this->result;
        }

        throw new \BadMethodCallException;
    }
}
