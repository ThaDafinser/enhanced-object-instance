<?php
namespace EnhancedObjectInstanceTest\Asset;

class SimpleCounter
{

    private $callCount = 0;

    public function getCallCount()
    {
        $this->callCount ++;
        
        return $this->callCount;
    }
}
