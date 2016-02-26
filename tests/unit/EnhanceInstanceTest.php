<?php
namespace UglyProxyManagerFunTest\Unit;

use PHPUnit_Framework_TestCase;
use UglyProxyManagerFun\EnhanceInstance;
use UglyProxyManagerFunTest\Asset\SimpleClass;
use Psr6NullCache\Adapter\MemoryCacheItemPool;
use UglyProxyManagerFunTest\Asset\SimpleClassWithParameters;

/**
 * @covers UglyProxyManagerFun\EnhanceInstance
 */
class EnhanceInstanceTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateInstanceThrowInvalidArgumentException()
    {
        $instance = 'not a object';
        
        $enhance = new EnhanceInstance($instance);
    }

    public function testCreateInstance()
    {
        $instance = new SimpleClass();
        
        $enhance = new EnhanceInstance($instance);
        $this->assertInstanceOf('UglyProxyManagerFunTest\Asset\SimpleClass', $enhance->getOriginalInstance());
        $this->assertInstanceOf('Psr\Cache\CacheItemPoolInterface', $enhance->getCache());
        $this->assertInstanceOf('Psr\Log\LoggerInterface', $enhance->getLogger());
    }

    public function testCustomClassCacheCounter()
    {
        $class = new \ReflectionClass('UglyProxyManagerFun\EnhanceInstance');
        $method = $class->getMethod('getCacheKey');
        $method->setAccessible(true);
        
        $instance = new SimpleClass();
        $this->assertEquals(1, $instance->getCallCount());
        $this->assertEquals(2, $instance->getCallCount());
        
        $memory = new MemoryCacheItemPool();
        
        $enhance = new EnhanceInstance($instance, $memory);
        $enhance->addMethodsCache([
            'getCallCount'
        ]);
        
        $cacheKey = $method->invokeArgs($enhance, [
            'getCallCount',
            []
        ]);
        
        // no cache result available
        $this->assertFalse($memory->hasItem($cacheKey));
        
        /* @var $instance \UglyProxyManagerFunTest\Asset\SimpleClass */
        $instance = $enhance->getAwesomeInstance();
        $this->assertEquals(3, $instance->getCallCount());
        
        // now the result should be cached!
        $this->assertTrue($memory->hasItem($cacheKey));
        
        // read from the cache - counter should still be 3!
        $this->assertEquals(3, $instance->getCallCount());
        $this->assertEquals(3, $instance->getCallCount());
    }

    public function testCustomClassCacheCounterWithParamters()
    {
        $instance = new SimpleClassWithParameters();
        $memory = new MemoryCacheItemPool();
        
        $enhance = new EnhanceInstance($instance, $memory);
        $enhance->addMethodsCache([
            'getCallCount'
        ]);
        
        /* @var $instance \UglyProxyManagerFunTest\Asset\SimpleClassWithParameters */
        $instance = $enhance->getAwesomeInstance();
        
        $this->assertEquals(1, $instance->getCallCount(1));
        
        // read from the cache - counter should still be 3!
        $this->assertEquals(1, $instance->getCallCount(1));
        
        // change the parameter -> no cache
        $this->assertEquals(2, $instance->getCallCount('something'));
        // new cache now saved
        $this->assertEquals(2, $instance->getCallCount('something'));
        // old cache still around
        $this->assertEquals(1, $instance->getCallCount(1));
        
        // 2nd paramter change
        $this->assertEquals(3, $instance->getCallCount('something', false));
        $this->assertEquals(4, $instance->getCallCount('something', true));
    }
}
