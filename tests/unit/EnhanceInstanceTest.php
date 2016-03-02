<?php
namespace EnhancedObjectInstanceTest\Unit;

use PHPUnit_Framework_TestCase;
use EnhancedObjectInstance\EnhanceInstance;
use Psr6NullCache\Adapter\MemoryCacheItemPool;
use EnhancedObjectInstanceTest\Asset\SimpleCounter;
use EnhancedObjectInstanceTest\Asset\SimpleCounterWithParameters;

/**
 * @covers EnhancedObjectInstance\EnhanceInstance
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
        $instance = new SimpleCounter();
        
        $enhance = new EnhanceInstance($instance);
        $this->assertInstanceOf('EnhancedObjectInstanceTest\Asset\SimpleCounter', $enhance->getOriginalInstance());
    }

    public function testCacheSimpleCounter()
    {
        $class = new \ReflectionClass('EnhancedObjectInstance\EnhanceInstance');
        $method = $class->getMethod('getCacheKey');
        $method->setAccessible(true);
        
        $instance = new SimpleCounter();
        $this->assertEquals(1, $instance->getCallCount());
        $this->assertEquals(2, $instance->getCallCount());
        
        $memory = new MemoryCacheItemPool();
        
        $enhance = new EnhanceInstance($instance);
        $enhance->addMethodCache('getCallCount', $memory);
        
        $cacheKey = $method->invokeArgs($enhance, [
            'getCallCount',
            []
        ]);
        
        // no cache result available
        $this->assertFalse($memory->hasItem($cacheKey));
        
        /* @var $instance \EnhancedObjectInstanceTest\Asset\SimpleCounter */
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
        $instance = new SimpleCounterWithParameters();
        $memory = new MemoryCacheItemPool();
        
        $enhance = new EnhanceInstance($instance, $memory);
        $enhance->addMethodCache('getCallCount', $memory);
        
        /* @var $instance \EnhancedObjectInstanceTest\Asset\SimpleCounterWithParameters */
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
