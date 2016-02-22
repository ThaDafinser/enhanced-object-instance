<?php
namespace UglyProxyManagerFun;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;
use Psr\Cache\CacheItemPoolInterface;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;

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

    /**
     *
     * @param object $instance            
     */
    public function __construct($instance, CacheItemPoolInterface $cache = null, LoggerInterface $logger = null)
    {
        $this->instance = $instance;
        
        if ($cache === null) {
            $cache = new NullCacheItemPool();
        }
        $this->setCache($cache);
        
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->setLogger($logger);
    }

    /**
     *
     * @param CacheItemPoolInterface $cache            
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     *
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     *
     * @param LoggerInterface $logger            
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
            $msg .= ' | Return: ' . $this->getValueAsPrintString($returnValue);
        }
        
        return $msg;
        
        $logger->log('warn', get_class($instance) . '::' . $method . '(' . implode(', ', $parameters) . ') POST | ' . microtime(true) . ' | Result: ' . $returnValue);
    }

    /**
     *
     * @return \ProxyManager\Proxy\AccessInterceptorInterface|\ProxyManager\Proxy\ValueHolderInterface
     */
    public function getAwesomeInstance()
    {
        $enhanceInstance = $this;
        $cache = $this->getCache();
        $logger = $this->getLogger();
        
        $prefixInterceptors = [];
        $suffixInterceptors = [];
        
        foreach ($this->methodInterceptors as $methodName => $settings) {
            
            /*
             * prefix
             */
            $prefixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, & $returnEarly) use($settings, $enhanceInstance, $cache, $logger) {
                
                // caching
                if (isset($settings['cache']['enabled']) && $settings['cache']['enabled'] === true) {
                    $key = md5(serialize($params));
                    
                    if ($cache->hasItem($key) === true) {
                        $returnEarly = true;
                        
                        $cacheItem = $cache->getItem($key);
                        
                        // logging - cacheHit
                        if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                            $logger->log($settings['logging']['level'], $enhanceInstance->formatLogMsg('cacheHit', $proxy, $instance, $method, $params, $cacheItem));
                        }
                        
                        return $cacheItem->get();
                    }
                }
                
                // logging
                if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                    $logger->log($settings['logging']['level'], $enhanceInstance->formatLogMsg('pre', $proxy, $instance, $method, $params));
                }
            };
            
            /*
             * suffix
             */
            $suffixInterceptors[$methodName] = function ($proxy, $instance, $method, $params, $returnValue, & $returnEarly) use($settings, $enhanceInstance, $cache, $logger) {
                
                // caching
                if (isset($settings['cache']['enabled']) && $settings['cache']['enabled'] === true) {
                    $key = md5(serialize($params));
                    
                    $item = $cache->getItem($key);
                    $item->set($returnValue);
                    
                    $cache->save($item);
                }
                
                // logging
                if (isset($settings['logging']['enabled']) && $settings['logging']['enabled'] === true) {
                    $logger->log($settings['logging']['level'], $enhanceInstance->formatLogMsg('post', $proxy, $instance, $method, $params, $returnValue));
                }
            };
        }
        
        $factory = new Factory();
        
        return $factory->createProxy($this->instance, $prefixInterceptors, $suffixInterceptors);
    }
}
