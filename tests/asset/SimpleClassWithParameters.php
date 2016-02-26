<?php
namespace UglyProxyManagerFunTest\Asset;

class SimpleClassWithParameters
{

    private $callCount = 0;

    public function getCallCount($first, $second = null)
    {
        $this->callCount ++;
        
        return $this->callCount;
    }
}
