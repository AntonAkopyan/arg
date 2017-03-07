<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.vlasyuk
 * Date: 10/1/14
 * Time: 5:28 PM
 */

namespace Library\Base;

class Container
{
    private $_singletons = array();

    public function get($class, array $params = array(), $is_singleton = false)
    {

        if ($is_singleton && isset($this->_singletons[$class]))
        {
            return $this->_singletons[$class];
        }

        $object = new $class;

        foreach ($params as $name => $value)
        {
            $object->$name = $value;
        }

        if ($is_singleton && !isset($this->_singletons[$class]))
        {
            $this->_singletons[$class] = $object;
        }

        return $object;
    }
}