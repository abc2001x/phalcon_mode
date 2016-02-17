<?php 
use Phalcon\DI\FactoryDefault;
// $a = yaml_parse_file(dirname(realpath('.')).'/config/config.yaml');
include dirname(__DIR__).'/app/Bootstrap.php';


$application = new Bootstrap(new FactoryDefault());
$application->initAll();

class Robots extends \Phalcon\Mvc\Model
{
    protected $id;

    protected $name;

    protected $price;
    
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
                $methodName = "set" . ucfirst($fieldName);
                $method = $R->getMethod($methodName);
                // Okay, no exception, let's call it
                return $this->$methodName($value);
            } catch(\ReflectionException $up) {
                // Just let it go through
                throw $up;
            }
        }
    }
    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        // The name is too short?
        if (strlen($name) < 10) {
            throw new \InvalidArgumentException('The name is too short');
        }
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPrice($price)
    {
        // Negative prices aren't allowed
        if ($price < 0) {
            throw new \InvalidArgumentException('Price can\'t be negative');
        }
        $this->price = $price;
    }

    public function getPrice()
    {
        // Convert the value to double before be used
        return (double) $this->price;
    }
}
$a = new Robots();
$a->name='wwww';
echo $a->name.PHP_EOL;