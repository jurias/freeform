<?php

namespace Freeform\Query;

class Query
{
  public static final function __callStatic($class, $args)
  {
    $class = __NAMESPACE__ . '\\' . $class;

    if (class_exists($class))
    {
      $reflection = new \ReflectionClass($class);

      return $reflection->newInstanceArgs($args);
    }
    else
    {
      throw new \Exception("Method not found");
    }
  }
}