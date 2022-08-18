<?php

namespace Amp\Cache\Test;

use Amp\Cache\Cache;
use Amp\Cache\LocalCache;
use Amp\Cache\PrefixCache;

class PrefixCacheTest extends CacheTest
{
    /** @return PrefixCache */
    protected function createCache(): Cache
    {
        return new PrefixCache(new LocalCache, "prefix.");
    }

    public function testPrefix(): void
    {
        $this->assertSame("prefix.", $this->createCache()->getKeyPrefix());
    }
}
