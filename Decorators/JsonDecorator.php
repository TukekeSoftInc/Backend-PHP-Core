<?php
/**
 * File defining Core\Decorators\ModelDecorator
 *
 * PHP Version 5.3
 *
 * @category  Backend
 * @package   Core/Decorators
 * @author    J Jurgens du Toit <jrgns@backend-php.net>
 * @copyright 2011 - 2012 Jade IT (cc)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @link      http://backend-php.net
 */
namespace Backend\Core\Decorators;
use \Backend\Core\Interfaces\DecorableInterface;
/**
 * Give custom JSON encoding functionality to objects
 *
 * @category Backend
 * @package  Core/Decorators
 * @author   J Jurgens du Toit <jrgns@backend-php.net>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT License
 * @link     http://backend-php.net
 */
class JsonDecorator implements \Backend\Core\Interfaces\DecoratorInterface
{
    /**
     * @var Object The object this class is decorating
     */
    protected $object;

    /**
     * The constructor for the class
     *
     * @param DecorableInterface $object The model to decorate
     */
    function __construct(DecorableInterface $object)
    {
        $this->object = $object;
    }

    /**
     * The magic _call function.
     *
     * This is used to call the specified function on the original object
     *
     * @param string $method The name of the method to call
     * @param array  $args   The arguments to pass to the method
     *
     * @return mixed The result of the called method
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->object, $method), $args);
    }

    /**
     * Get the normal properties of the Object
     *
     * Normal is defined as all public and protected properties that does not start with an underscore
     *
     * @return array The normal properties of the object
     */
    public function getProperties()
    {
        if (function_exists($this->object, 'getProperties')) {
            return $this->object->getProperties();
        }
        $reflector  = new \ReflectionClass($this);
        $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        $result     = array();
        foreach ($properties as $property) {
            if ($property->isPrivate() || substr($property->getName(), 0, 1) == '_') {
                continue;
            }
            $result[$property->getName()] = $this->{$property->getName()};
        }
        return $result;
    }

    /**
     * JSON encode the object, including all properties
     *
     * @return string The json encoded object
     */
    public function toJson()
    {
        $properties     = $this->object->getProperties();
        $object         = new \StdClass();
        $object->_class = get_class($this->object);
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        return json_encode($object);
    }
}