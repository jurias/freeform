<?php

namespace Freeform;

class Validators
{
  public static function integer($input)
  {
    $message = "must be an integer";
    return self::run(is_numeric($input), $message);
  }

  public static function not_empty($input)
  {
    $message = "cannot be empty";
    return self::run($input > '', $message);
  }

  public static function date($input)
  {
    $message = "must be a valid date";
    return self::run(true, $message);
  }

  public static function datetime($input)
  {
    $message = "must be a valid date and time";
    return self::run(true, $message);
  }

  public static function run($condition, $message)
  {
    if ($condition !== true)
    {
      return array('error' => $message);
    }
  }
}