<?php

spl_autoload_register(function($class){
  $class = str_replace('Freeform\\', '', $class);
  $ds = DIRECTORY_SEPARATOR;
  $src_dir = __DIR__ . $ds . 'src' . $ds . 'Freeform' . $ds;
  $file = $src_dir . str_replace('\\', $ds, $class) . '.php';
  if (is_readable($file))
  {
    include_once $file;
  }
});
