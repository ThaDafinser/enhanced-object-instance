
# UglyProxyManagerFun (PROTOTYPE!!!)

Target: reusable cache/logging/tracing/... proxy based on ProxyManager

## Example
```php
require 'vendor/autoload.php';

use UglyProxyManagerFun\EnhanceInstance;

class Heavy
{

    public function loadSlow(array $first = [], $second = true)
    {
        sleep(1);
        
        return 'yippie';
    }

    public function loadFast()
    {
        return [
            'something'
        ];
    }
}

$heavyInstance = new Heavy();

$test = new EnhanceInstance($heavyInstance);
$test->addMethodsCache([
    'loadSlow'
]);
$test->addMethodsLogging([
    'loadSlow',
    'loadFast'
]);

/* @var $heavyInstance \Heavy */
$heavyInstance = $test->getAwesomeInstance();
$heavyInstance->loadSlow([
    'a',
    'b'
], false);
$heavyInstance->loadFast();

```

## Example output

```cli
C:\Data\wap\htdocs\GitHub\UglyProxyManagerFun>php test.php
PRE        Heavy::loadSlow([0 => 'a', 1 => 'b'], false)
POST       Heavy::loadSlow([0 => 'a', 1 => 'b'], false) | Return: 'yippie'
PRE        Heavy::loadFast()
POST       Heavy::loadFast() | Return: [0 => 'something']
DONE
C:\Data\wap\htdocs\GitHub\UglyProxyManagerFun>
```
