<?php
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

echo 'DONE';
