<?php
namespace UglyProxyManagerFunTest\Asset;

class SimpleClass
{

    private $callCount = 0;

    public function getCallCount()
    {
        $this->callCount ++;
        
        return $this->callCount;
    }
}
