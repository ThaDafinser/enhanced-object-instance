
# PSR logging and caching for all object instances

[![Build Status](https://travis-ci.org/ThaDafinser/enhanced-object-instance.svg)](https://travis-ci.org/ThaDafinser/enhanced-object-instance)
[![Code Coverage](https://scrutinizer-ci.com/g/ThaDafinser/enhanced-object-instance/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ThaDafinser/enhanced-object-instance/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ThaDafinser/enhanced-object-instance/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ThaDafinser/enhanced-object-instance/?branch=master)
[![PHP 7 ready](http://php7ready.timesplinter.ch/ThaDafinser/enhanced-object-instance/badge.svg)](https://travis-ci.org/ThaDafinser/enhanced-object-instance)

[![Latest Stable Version](https://poser.pugx.org/thadafinser/enhanced-object-instance/v/stable)](https://packagist.org/packages/thadafinser/enhanced-object-instance)
[![Latest Unstable Version](https://poser.pugx.org/thadafinser/enhanced-object-instance/v/unstable)](https://packagist.org/packages/thadafinser/enhanced-object-instance) 
[![License](https://poser.pugx.org/thadafinser/enhanced-object-instance/license)](https://packagist.org/packages/thadafinser/enhanced-object-instance)
[![Total Downloads](https://poser.pugx.org/thadafinser/enhanced-object-instance/downloads)](https://packagist.org/packages/thadafinser/enhanced-object-instance) 

Instant ***caching*** and ***logging (WIP)*** for all object instances.

Thank's to the PHP-FIG it's now possible!
- [PSR-3 logging](http://www.php-fig.org/psr/psr-3/)
- [PSR-6 caching](http://www.php-fig.org/psr/psr-6/)


## When i need this?

- You need caching/logging for a package you consume, which does not provide it
- You currently develop a class/package and need caching/logging and dont want to waste your time

## Example
```php
require 'vendor/autoload.php';

use Psr6NullCache\Adapter\MemoryCacheItemPool;
use EnhancedObjectInstance\EnhanceInstance;

class Heavy
{

    private $callCount = 0;

    public function getCallCount()
    {
        $this->callCount ++;
        
        return $this->callCount;
    }
}

// your PSR-6 cache adapter - https://packagist.org/providers/psr/cache-implementation
$cache = MemoryCacheItemPool();

$test = new EnhanceInstance(new Heavy());
$test->addMethodCache('getCallCount', $cache);

/* @var $heavyInstance \Heavy */
$heavyInstance = $test->getAwesomeInstance();

echo $heavyInstance->getCallCount(); // output 1
echo $heavyInstance->getCallCount(); // output 1, because cached :-)
```
