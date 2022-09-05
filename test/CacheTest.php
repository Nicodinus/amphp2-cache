<?php

namespace Amp\Cache\Test;

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\Payload;
use Amp\Cache\Cache;
use Amp\Emitter;
use Amp\PHPUnit\AsyncTestCase;
use function Amp\asyncCall;
use function Amp\delay;
use function Amp\Iterator\toArray;

abstract class CacheTest extends AsyncTestCase
{
    abstract protected function createCache(): Cache;

    public function testGet(): \Generator
    {
        $cache = $this->createCache();

        $this->assertNull(yield $cache->get("mykey"));

        yield $cache->set("mykey", "myvalue", 10);
        $this->assertSame("myvalue", yield $cache->get("mykey"));

        yield $cache->delete("mykey");
    }

    public function testGetIterator(): \Generator
    {
        $cache = $this->createCache();

        $result = yield toArray($cache->getIterator("mykey"));
        $this->assertTrue(\sizeof($result) == 0);

        $emitter = new Emitter();

        asyncCall(function () use (&$emitter) {
            yield $emitter->emit("myvalue");
            $emitter->complete();
        });

        yield $cache->setIterator("mykey", $emitter->iterate(), 10);

        $result = yield toArray($cache->getIterator("mykey"));
        $this->assertTrue(\sizeof($result) == 1);
        $result = \array_pop($result);
        $this->assertTrue(\sizeof($result) == 1);
        $result = \array_pop($result);
        $this->assertSame("myvalue", $result);

        yield $cache->delete("mykey");
    }

    public function testGetStream(): \Generator
    {
        $cache = $this->createCache();

        $result = yield (new Payload($cache->getStream("mykey")))->buffer();
        $this->assertTrue(\strlen($result) == 0);

        yield $cache->setStream("mykey", new InMemoryStream("myvalue"), 10);
        $result = yield (new Payload($cache->getStream("mykey")))->buffer();
        $this->assertSame("myvalue", $result);

        yield $cache->delete("mykey");
    }

    public function testEntryIsNotReturnedAfterTTLHasPassed(): \Generator
    {
        $cache = $this->createCache();

        yield $cache->set("foo", "bar", 0);
        yield delay(10);
        $this->assertNull(yield $cache->get("foo"));
    }

    public function testEntryIsReturnedWhenOverriddenWithNoTimeout(): \Generator
    {
        $cache = $this->createCache();

        yield $cache->set("foo", "bar", 0);
        yield $cache->set("foo", "bar");

        $this->assertNotNull(yield $cache->get("foo"));
    }

    public function testEntryIsNotReturnedAfterDelete(): \Generator
    {
        $cache = $this->createCache();

        yield $cache->set("foo", "bar");
        yield $cache->delete("foo");

        $this->assertNull(yield $cache->get("foo"));
    }
}
