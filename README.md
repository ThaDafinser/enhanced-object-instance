
# PSR logging and caching for all object instances

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

use Psr6NullCache\Adapter\NullCacheItemPool;
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
$cache = NullCacheItemPool();

$test = new EnhanceInstance(new Heavy());
$test->addMethodCache('getCallCount', $cache);

/* @var $heavyInstance \Heavy */
$heavyInstance = $test->getAwesomeInstance();

echo $heavyInstance->getCallCount(); // output 1
echo $heavyInstance->getCallCount(); // output 1, because cached :-)
```
