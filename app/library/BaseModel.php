<?php
namespace Library;

class BaseModel extends \Phalcon\Mvc\Model
{

    public function __set($fieldName, $value)
    {
        // Check if the property is public
        try {
            $R = new \ReflectionClass($this);
            $property = $R->getProperty($fieldName);
        } catch(\ReflectionException $e) {
            // Property doesn't exist, call the stupid parent
            return parent::__set($fieldName, $value);
        }
        if ($property->isPublic()) {
            // Again, call your parents
            return parent::__set($fieldName, $value);
        } else {
            // Property exists, and it's private / protected
            try {
                // Maybe there is a setter for this one?
                $nameArr = explode('_',$fieldName);
                $methodName = 'set';
                foreach ($nameArr as $vv) {
                    $methodName.=ucwords($vv);
                }
                $method = $R->getMethod($methodName);
                // Okay, no exception, let's call it
                return $this->$methodName($value);
            } catch(\ReflectionException $up) {
                // Just let it go through
                throw $up;
            }
        }
    }
}