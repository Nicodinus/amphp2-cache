<?php

namespace Amp\Cache\Internal;

use Amp\Failure;
use Amp\Iterator;
use Amp\Promise;
use Amp\Success;

/**
 * @template T
 * @internal
 */
class CompletedIterator implements Iterator
{
    /** @var \Throwable|null */
    private ?\Throwable $throwable;

    /** @var T */
    private $result;

    /** @var Promise|null */
    private ?Promise $advanced;

    //

    /**
     * @param \Throwable|null $throwable
     * @param T $result
     */
    protected function __construct(?\Throwable $throwable = null, $result = null)
    {
        $this->throwable = $throwable;
        $this->result = $result;

        $this->advanced = null;
    }

    /**
     * @return void
     */
    public function __sleep(): void
    {
        throw new \Error(__CLASS__ . ' does not support serialization');
    }

    /**
     * @return void
     */
    protected function __clone()
    {
        // clone is automatically denied to all external calls
        // final protected instead of private to also force denial for all children
    }

    /**
     * @param mixed|null $result
     *
     * @return static<T>
     *
     * @psalm-suppress UnsafeInstantiation
     */
    public static function complete($result = null): self
    {
        return new static(null, $result);
    }

    /**
     * @param \Throwable $throwable
     *
     * @return static<T>
     *
     * @psalm-suppress UnsafeInstantiation
     */
    public static function fail(\Throwable $throwable): self
    {
        return new static($throwable);
    }

    /**
     * @inheritDoc
     */
    public function advance(): Promise
    {
        if ($this->advanced !== null) {
            return $this->advanced;
        }

        if ($this->throwable !== null) {
            $this->advanced = new Failure($this->throwable);
            return $this->advanced;
        }

        $this->advanced = new Success(false);

        if ($this->result === null) {
            return $this->advanced;
        }
        return new Success(true);
    }

    /**
     * @inheritDoc
     */
    public function getCurrent()
    {
        return $this->result;
    }
}
