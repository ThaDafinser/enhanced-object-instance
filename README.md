
# UglyProxyManagerFun (PROTOTYPE!!!)

Many packages have implementations, that the user can cache or log a request.


This would not be necessary, since this can be also achieved by a Proxy (in most cases)
 https://ocramius.github.io/ProxyManager/docs/access-interceptor-value-holder.html


The proxy pattern is at least for some end users (package consumers) to complicated...that's there this package come into place.


The `UglyProxyManagerFun\EnhanceInstance` class should make it very easy to add a logger or a cache to any existing package.
Also the package maintainer could add this lib and reference it in the documentation for the enduser


Benefits
- no seperate log/cache code needed anymore in each package
- not 1000 different ways for the end users, how you need to activate caching / logging 


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

$test = new EnhanceInstance(new Heavy());
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
