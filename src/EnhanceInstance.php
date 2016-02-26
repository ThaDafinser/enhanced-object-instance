<?php
namespace UglyProxyManagerFun;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Cache\CacheItemPoolInterface;
use Psr6NullCache\Adapter\NullCacheItemPool;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use InvalidArgumentException;

class EnhanceInstance
{

    private $instance;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var CacheItemPoolInterface
     */
    private $cache;

    private $methodInterceptors = [];

    private $startTime = [];

    /**
     *
     * @param object $instance            
     * @param CacheItemPoolInterface $cache            
     * @param LoggerInterface $logger            
     */
    public function __construct($instance, CacheItemPoolInterface $cache = null, LoggerInterface $logger = null)
    {
        if (! is_object($instance)) {
            throw new InvalidArgumentException('instance parameter must be a object');
        }
        
        $this->instance = $instance;
        
        if ($cache === null) {
            $cache = new NullCacheItemPool();
        }
        $this->cache = $cache;
        
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
    }

    public function getOriginalInstance()
    {
        return $this->instance;
    }

    /**
     *
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    private function getCacheKey($method, $parameters)
    {
        return md5($method . serialize($parameters));
    }

    /**
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function addMethodsCache(array $methodNames = [])
    {
        foreach ($methodNames as $methodName) {
            if (! isset($this->methodInterceptors[$methodName])) {
                $this->methods[$methodName] = [];
            }
            
            $this->methodInterceptors[$methodName]['cache'] = [
                'enabled' => true
            ];
        }
    }

    public function addMethodsLogging(array $methodNames = [], $level = LogLevel::DEBUG)
    {
        foreach ($methodNames as $methodName) {
            if (! isset($this->methodInterceptors[$methodName])) {
                $this->methods[$methodName] = [];
            }
            
            $this->methodInterceptors[$methodName]['logging'] = [
                'enabled' => true,
                'level' => $level
            ];
        }
    }

    private function getParametersToString($params)
    {
        $parameters = [];
        
        foreach ($params as $param) {
            $parameters[] = $this->getValueAsPrintString($param);
        }
        
        return $parameters;
    }

    private function getValueAsPrintString($returnValue)
    {
        if (is_array($returnValue)) {
            $serializedArray = [];
            foreach ($returnValue as $key => $value) {
                if (! is_scalar($value)) {
                    return 'array';
                }
                
                if (! is_numeric($key)) {
                    $key = '\'' . $key . '\'';
                }
                if (! is_numeric($value)) {
                    $value = '\'' . $value . '\'';
                }
                
                $part = $key . ' => ' . $value;
                
                $serializedArray[] = $part;
            }
            
            return '[' . implode(', ', $serializedArray) . ']';
        }
        
        if (is_object($returnValue)) {
            return 'object';
        }
        
        if (is_bool($returnValue)) {
            if ($returnValue === true) {
                return 'true';
            }
            
            return 'false';
        }
        
        if (is_null($returnValue)) {
            return 'null';
        }
        
        if (is_numeric($returnValue)) {
            return $returnValue;
        }
        
        if (is_string($returnValue)) {
            return '\'' . $returnValue . '\'';
        }
        
        return $returnValue;
    }

    private function formatLogMsg($type, $proxy, $instance, $method, $params, $returnValue = null)
    {
        $msg = strtoupper(str_pad($type, 10, ' '));
        
        $msg .= ' ' . get_class($instance) . '::' . $method . '(' . implode(', ', $this->getParametersToString($params)) . ')';
        
        if ($type == 'post' || $type == 'cacheHit') {
            if (isset($this->startTime[$method])) {
                $usedTime = microtime(true) - $this->startTime[$method];
                
                $msg .= ' | Time used: ' . round($usedTime, 6);
            }
            $msg .= ' | Return: ' . $this->getValueAsPrintString($returnValue);
        } elseif ($type == 'pre') {
            $this->startTime[$method] = microtime(true);
        }
        
        return $msg;
    }

    /**
     *
     * @return \ProxyManager\Proxy\AccessInterceptorInterface|\ProxyManager\Proxy\ValueHolderInterface
     */
    public function getAwesomeInstance()
    {
        $enhancedInstance = $this;
        $cache = $this->getCache();
        $logger = $this->getLogger();
        
        $prefixInterceptors = [];
        $suffixInterceptors = [];
        
        foreach ($this->methodInterceptors as $methodName => $settings) {
            
            /*
             * prefix
             */
            $prefixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, & $returnEarly) use ($settings, $enhancedInstance, $cache,$logger) {
                
                // caching
                if (isset($settings['cache']['enabled']) && $settings['cache']['enabled'] === true) {
                    if ($cache->hasItem($this->getCacheKey($method, $params)) === true) {
                        $returnEarly = true;
                        
                        $cacheItem = $cache->getItem($this->getCacheKey($method, $params));
                        
                        // logging - cacheHit
                        if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                            $logger->log($settings['logging']['level'], $enhancedInstance->formatLogMsg('cacheHit', $proxy, $instance, $method, $params, $cacheItem));
                        }
                        
                        return $cacheItem->get();
                    }
                }
                
                // logging
                if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                    $logger->log($settings['logging']['level'], $enhancedInstance->formatLogMsg('pre', $proxy, $instance, $method, $params));
                }
            };
            
            /*
             * suffix
             */
            $suffixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, $returnValue, & $returnEarly) use ($settings, $enhancedInstance, $cache,$logger) {
                
                // caching
                if (isset($settings['cache']['enabled']) && $settings['cache']['enabled'] === true) {
                    $item = $cache->getItem($this->getCacheKey($method, $params));
                    $item->set($returnValue);
                    
                    $cache->save($item);
                }
                
                // logging
                if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                    $logger->log($settings['logging']['level'], $enhancedInstance->formatLogMsg('post', $proxy, $instance, $method, $params, $returnValue));
                }
            };
        }
        
        $factory = new Factory();
        
        return $factory->createProxy($this->instance, $prefixInterceptors, $suffixInterceptors);
    }
}
