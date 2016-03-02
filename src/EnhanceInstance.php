<?php
namespace EnhancedObjectInstance;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use InvalidArgumentException;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;

class EnhanceInstance
{
    use CacheTrait;

    private $instance;

    private $methodInterceptors = [];

    private $startTime = [];

    /**
     *
     * @param object $instance            
     */
    public function __construct($instance)
    {
        if (! is_object($instance)) {
            throw new InvalidArgumentException('instance parameter must be a object');
        }
        
        $this->instance = $instance;
    }

    /**
     *
     * @return object
     */
    public function getOriginalInstance()
    {
        return $this->instance;
    }

    /**
     *
     * @return AccessInterceptorValueHolderFactory
     */
    public function getAwesomeInstance()
    {
        $enhancedInstance = $this;
        
        $prefixInterceptors = [];
        $suffixInterceptors = [];
        
        foreach ($this->getMethodsCache() as $methodName => $cache) {
            
            /*
             * prefix
             */
            $prefixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, &$returnEarly) use ($enhancedInstance, $cache) {
                
                // caching
                if ($cache->hasItem($this->getCacheKey($method, $params)) === true) {
                    $returnEarly = true;
                    
                    $cacheItem = $cache->getItem($this->getCacheKey($method, $params));
                    
                    return $cacheItem->get();
                }
            };
            
            /*
             * suffix
             */
            $suffixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, $returnValue, &$returnEarly) use ($enhancedInstance, $cache) {
                
                // caching
                $item = $cache->getItem($this->getCacheKey($method, $params));
                $item->set($returnValue);
                
                $cache->save($item);
            };
        }
        
        /*
         * Create the awesome proxy with interceptors!
         */
        $factory = new Factory();
        
        return $factory->createProxy($this->instance, $prefixInterceptors, $suffixInterceptors);
    }
}
