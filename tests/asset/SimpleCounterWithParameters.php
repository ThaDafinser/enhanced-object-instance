<?php
namespace EnhancedObjectInstanceTest\Asset;

class SimpleCounterWithParameters
{

    private $callCount = 0;

    public function getCallCount($first, $second = null)
    {
        $this->callCount ++;
        
        return $this->callCount;
    }
}
