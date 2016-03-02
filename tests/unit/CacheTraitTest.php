<?php
namespace EnhancedObjectInstanceTest\Unit;

use PHPUnit_Framework_TestCase;
use Psr6NullCache\Adapter\NullCacheItemPool;

/**
 * @covers EnhancedObjectInstance\CacheTrait
 */
class CacheTraitTest extends PHPUnit_Framework_TestCase
{

    public function testGetCacheKey()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $this->assertEquals('825f31041931ef9a433ae2f5e703706c', $trait->getCacheKey('asdf', []));
    }

    public function testAddMethodCache()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $cache = new NullCacheItemPool();
        $trait->addMethodCache('myMethod', $cache);
        
        $this->assertCount(1, $trait->getMethodsCache());
        $this->assertSame($cache, $trait->getMethodsCache()['myMethod']);
    }

    public function testAddMethodCacheOverwrite()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $cache = new NullCacheItemPool();
        $trait->addMethodCache('myMethod', $cache);
        
        $cache2 = new NullCacheItemPool();
        $trait->addMethodCache('myMethod', $cache2);
        
        $this->assertCount(1, $trait->getMethodsCache());
        $this->assertSame($cache2, $trait->getMethodsCache()['myMethod']);
    }

    public function testAddMethodCacheMultiple()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $cache = new NullCacheItemPool();
        $trait->addMethodCache('myMethod', $cache);
        
        $cache2 = new NullCacheItemPool();
        $trait->addMethodCache('myMethod2', $cache2);
        
        $this->assertCount(2, $trait->getMethodsCache());
        $this->assertSame($cache, $trait->getMethodsCache()['myMethod']);
        $this->assertSame($cache2, $trait->getMethodsCache()['myMethod2']);
    }

    public function testRemoveMethodCache()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $cache = new NullCacheItemPool();
        
        $trait->addMethodCache('myMethod', $cache);
        $this->assertCount(1, $trait->getMethodsCache());
        
        $trait->removeMethodCache('uncachedMethod');
        $this->assertCount(1, $trait->getMethodsCache());
        
        $trait->removeMethodCache('myMethod');
        $this->assertCount(0, $trait->getMethodsCache());
    }

    public function testResetMethodCache()
    {
        $trait = $this->getMockForTrait('EnhancedObjectInstance\CacheTrait');
        
        $cache = new NullCacheItemPool();
        
        $trait->addMethodCache('myMethod', $cache);
        $this->assertCount(1, $trait->getMethodsCache());
        
        $trait->resetMethodsCache();
        $this->assertCount(0, $trait->getMethodsCache());
    }
}
