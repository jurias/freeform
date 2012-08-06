<?php

spl_autoload_register(function($class){
  $class = str_replace('Freeform\\', '', $class);
  $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
  if (is_readable($file))
  {
    include_once $file;
  }
});
