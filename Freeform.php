<?php

namespace Freeform;

use PDO;

class Freeform
{
  public static $db;
 
  private static $db_defaults =
    array(
      'datasource'  => 'mysql',
      'host'        => 'localhost',
      'user'        => 'root',
      'password'    => '',
    );

  public static $models;

  public static $schema;

  public static function connect($config)
  {
    $config += self::$db_defaults;

    $datasource = $config['datasource'];
    $host = $config['host'];
    $database = $config['database'];
    
    self::$db = new PDO("$datasource:host=$host;dbname=$database", $config['user'], $config['password']);
    self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

    self::initialize_table_data();
  }

  private static function initialize_table_data()
  {
    $schema = array();

    $sql = "SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_KEY
            FROM information_schema.columns 
            WHERE TABLE_SCHEMA = ?";
    $query = self::$db->prepare($sql);
    $query->execute(array('orm_test'));
    
    foreach($query->fetchAll(PDO::FETCH_ASSOC) as $column)
    {
      self::$schema[$column['TABLE_NAME']][$column['COLUMN_NAME']] = $column;
    }
  }
}