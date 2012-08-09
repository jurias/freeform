<?php

namespace Freeform;

use Freeform\Query\Query;
use Freeform\Lib\Helpers;
use Freeform\Lib\Inflector;

class Model
{
  static $table;
 
  static $primary_key = 'id';
  
  static $has_one = array();

  static $has_many = array();
  
  static $belongs_to = array();
  
  static $many_to_many = array();

  static $validate = array();

  private static $metadata;

  private $fields;

  public function __construct(array $values = null)
  {
    if ($values)
    {
      foreach ($values as $field => $value) {
        $this->$field = $value;
      }
    }

    $table = Helpers::tableize(get_called_class());
    foreach(Freeform::$schema[$table] as $field => $attrs)
    {
      $this->$field = isset($this->$field) ? $this->$field : null;
      $this->fields[] = $field;
    }
  }

  public function getFields()
  {
    return $this->fields;
  }

  /**
   * Returns metadata about the classes/tables
   *
   */
  private static function get($var)
  {
    $class = get_called_class();
    if (!isset(self::$metadata[$class]))
    {
      $reflector = new \ReflectionClass($class);
      $data = $reflector->getDefaultProperties();
      $data['class'] = $class;
      $data['table'] = $data['table'] ?: Helpers::tableize($class);

      $data['has_many'] = self::init_has_many($data['has_many']);
      $data['belongs_to'] = self::init_belongs_to($data['belongs_to']);
      $data['many_to_many'] = self::init_many_to_many($data['many_to_many']);

      self::$metadata[$class] = $data;
    }

    return self::$metadata[$class][$var];
  }

  private static function init_belongs_to($relations)
  {
    $ns_class = get_called_class();
    $class = explode('\\', $ns_class);
    $class = end($class);
    $relations = (array) $relations;
    $reflector = new \ReflectionClass($ns_class);
    $ns = $reflector->getNamespaceName();
      
    $formatted = array();
    foreach ($relations as $key => $relation) {
      if (is_string($relation))
        $formatted[$relation] = array();
      else
        $formatted[$key] = $relation;
    }
    $relations = $formatted;

    foreach ($relations as $model => &$settings) {
      $defaults = 
      array(
        "class" => "$ns\\" . Inflector::classify($model),
        "local_key" => Inflector::underscore($model) . "_id",
        "foreign_key" => "id",

      );
      $settings += $defaults;
    }

    return $relations;
  }

  /**
   * Initialize has_many relations.
   *
   */
  private static function init_has_many($relations)
  {
    $ns_class = get_called_class();
    $class = explode('\\', $ns_class);
    $class = end($class);
    $relations = (array) $relations;
    $reflector = new \ReflectionClass($ns_class);
    $ns = $reflector->getNamespaceName();
      
    $formatted = array();
    foreach ($relations as $key => $relation) {
      if (is_string($relation))
        $formatted[$relation] = array();
      else
        $formatted[$key] = $relation;
    }
    $relations = $formatted;

    foreach ($relations as $model => &$settings) {
      $defaults = 
      array(
        "class" => "$ns\\" . Inflector::classify($model),
        "table" => Inflector::tableize($model),
        "local_key" => "id",
        "foreign_key" => Inflector::underscore($class) . "_id",
      );
      $settings += $defaults;
    }

    return $relations;
  }

  /**
   * Initialize many_to_many relations.
   *
   */
  private static function init_many_to_many($relations)
  {
    $ns_class = get_called_class();
    $class = explode('\\', $ns_class);
    $class = end($class);
    $relations = (array) $relations;
    $reflector = new \ReflectionClass($ns_class);
    $ns = $reflector->getNamespaceName();
      
    $formatted = array();
    foreach ($relations as $key => $relation) {
      if (is_string($relation))
        $formatted[$relation] = array();
      else
        $formatted[$key] = $relation;
    }
    $relations = $formatted;

    foreach ($relations as $model => &$settings) {
      $classes = array(Inflector::tableize($class), Inflector::tableize($model));
      sort($classes);

      $defaults = 
      array(
        "class" => "$ns\\" . Inflector::classify($model),
        "table" => Inflector::tableize($model),
        "local_key" => "id",
        "foreign_key" => Inflector::underscore($class) . "_id",
        
        "join_table" => join('_',$classes),
        "join_table_this_key" => '',
        "join_table_that_key" => '',
        "target_table" => '',
        "target_table_key" => '',
      );
      $settings += $defaults;
    }

    // print_r($relations); die;

    return $relations;
  }

  private function getData()
  {
    $data = array();
    $fields = $this->fields;
    foreach ($fields as $field) {
      $data[$field] = $this->$field;
    }

    return $data;
  }

  /**
   * Find
   *
   * return array
   */
  public static function find()
  {
    $class = self::get('class');

    $conditions = array();

    $args = func_get_args();

    $conditions = (array) (count($args) == 1 ? pos($args) : $args);

    $query = Query::select($class)->from(self::get('table'));

    foreach($conditions as $key => $condition)
    {
      if (is_numeric($key) and (ctype_alnum((string) $condition) or $condition == ''))
      {
        $key = self::get('primary_key');
        $query->limit(1)->return_one();
      }

      if (is_numeric($key))
      {
        $query->where($condition);
      }
      else
      {
        $query->where("$key = ?");
        $query->bind($condition);
      }
    }

    $results = $query->execute();

    return $results;
  }

  public static function search($params, $operator = 'OR')
  {
    $class = get_called_class();
    $table = Helpers::tableize($class);

    $conditions = array();

    foreach($params as $field => $value)
    {
      $conditions[] = "$field LIKE ?";
      $bound_params[] = $value;
    }

    $query = Query::select($class)->from($table);
    
    $query->where(join(" $operator ", $conditions));

    $query->bind($bound_params);

    $results = $query->execute();

    return $results;
  }

  public function save()
  {
    if (!$this->validate())
    {
      return false;
    }

    $table = Helpers::tableize(get_called_class());
    $primary_key = static::$primary_key;

    $vars = $this->getData();
    $fields = array_keys($vars);
    $values = array_values($vars);

    $query = Query::update($table)->
               fields($fields)->
               values(array_fill(0, count($values), "?"))->
               bind($values);

    $result = $query->execute();

    if (!isset($this->id))
    {
      $this->id = Freeform::$db->lastInsertId();
    }

    return $result;
  }

  public function update($params)
  {
    foreach ($params as $field => $value) {
      $this->$field = $value;
    }

    return $this->save();
  }

  public function delete()
  {
    $class = get_called_class();
    $table = Helpers::tableize($class);
    $primary_key = $class::$primary_key;

    $fields = array($primary_key);
    $values = array($this->$primary_key);

    $query = Query::delete($table)->
               fields($fields)->
               values(array_fill(0, count($values), "?"))->
               bind($values);

    $result = $query->execute();

    return $result;
  }

  /**
   * Magic getter.  Fetches relation records.
   *
   */
  public function __get($property)
  {
    $class = get_called_class();

    $has_many = self::get('has_many');
    $belongs_to = self::get('belongs_to');
    $many_to_many = self::get('many_to_many');
    
    if (isset($has_many[$property]))
    {
      $relation = $has_many[$property];
      $params = array($relation['foreign_key'] => $this->$relation['local_key']);
      $results = $relation['class']::find($params);

      return $results;
    }
    if (isset($belongs_to[$property]))
    {
      $relation = $belongs_to[$property];
      $params = array($relation['foreign_key'] => $this->$relation['local_key']);
      $results = $relation['class']::find($params);

      return $results[0];
    }
    if (isset($many_to_many[$property]))
    {
      $relation = $many_to_many[$property];
      $join_table = $relation['join_table'];

      return $relation['class']::find();
    }
    
    throw new \Exception("Undefined property: $class::$property");
  }

  public function validate()
  {
    $errors = array();

    foreach (static::$validate as $field => $validators)
    {
      foreach ($validators as $validator => $options) {
        if (is_numeric($validator))
        {
          $validator = $options;
          $options = null;
        }
        $result = Validators::$validator($this->$field, $options);
        if ($result['error'])
        {
          $errors[] = $field . ' ' . $result['error'];
        }
      }
    }

    $this->errors = $errors;

    return empty($errors);
  }
}