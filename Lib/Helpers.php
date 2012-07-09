<?php

namespace Freeform\Lib;

class Helpers
{
  public static function tableize($class)
  {
    $class = explode("\\", $class);
    $class = end($class); 

    return Inflector::tableize($class);
  }

  public static function get_namespace($class)
  {
    $class = explode('\\', $class);
    array_pop($class);
    return '\\' . join('\\', $class);
  }
}