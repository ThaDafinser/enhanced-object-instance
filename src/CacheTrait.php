<?php
namespace EnhancedObjectInstance;

use Psr\Cache\CacheItemPoolInterface;

trait CacheTrait
{

    /**
     *
     * @var array
     */
    private $methodsCache = [];

    /**
     *
     * @param string $method            
     * @param array $parameters            
     */
    public function getCacheKey(string $method, array $parameters)
    {
        return md5($method . serialize($parameters));
    }

    /**
     *
     * @param string $name            
     * @param CacheItemPoolInterface $cache            
     */
    public function addMethodCache(string $name, CacheItemPoolInterface $cache)
    {
        $this->methodsCache[$name] = $cache;
    }

    /**
     *
     * @param string $name            
     */
    public function removeMethodCache(string $name)
    {
        unset($this->methodsCache[$name]);
    }

    public function resetMethodsCache()
    {
        $this->methodsCache = [];
    }

    /**
     *
     * @return array
     */
    public function getMethodsCache()
    {
        return $this->methodsCache;
    }
}
