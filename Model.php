<?php

namespace Freeform;

use Freeform\Freeform;
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
      $data['model'] = explode('\\', $class);
      $data['model'] = end($data['model']);
      $data['class'] = $class;
      $data['namespace'] = $reflector->getNamespaceName();
      $data['table'] = $data['table'] ?: Helpers::tableize($class);

      // Format relation data
      $relation_types = array('has_one', 'has_many', 'belongs_to', 'many_to_many');
      foreach ((array) $relation_types as $relation_type) {
        $data[$relation_type] = (array) $data[$relation_type];
        foreach ($data[$relation_type] as $key => $value) {
          if (is_scalar($value)) {
            $data[$relation_type][$value] = array();
            unset($data[$relation_type][$key]);                        
          }
        }
      }

      self::$metadata[$class] = $data;
    }

    return self::$metadata[$class][$var];
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

  public static function search($params = array(), $operator = 'OR')
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
    $relation_types = array('has_one', 'has_many', 'belongs_to', 'many_to_many');
    foreach ($relation_types as $relation_type) {
      $relations = self::get($relation_type);
      if (array_key_exists($property, $relations)) {
        return $this->$relation_type($property, $relations[$property]);
      }
    }

    throw new \Exception("Undefined property: " . get_called_class() . "::$property");
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

  public function belongs_to($model, $options = array())
  {
    if (!class_exists($model))
      $model = self::get('namespace') . '\\' . $model;

    $defaults = array(
      'class' => Inflector::classify($model),
      'local_key' => Helpers::propertize($model) . '_id',
      'foreign_key' => 'id',
    );
    $options = $options + $defaults;
    $query = array($options['foreign_key'] => $this->{$options['local_key']});
    $result = $options['class']::find($query);

    return pos($result);
  } 

  public function has_one($model, $options = array())
  {
    $result = call_user_func_array("self::has_many", func_get_args());
    return pos($result);
  } 

  public function has_many($model, $options = array())
  {
    if (!class_exists($model))
      $model = self::get('namespace') . '\\' . $model;

    $defaults = array(
      'class' => Inflector::classify($model),
      'local_key' => 'id',
      'foreign_key' => Inflector::underscore($this->get('model')) . '_id',
    );
    $options = $options + $defaults;
    $query = array($options['foreign_key'] => $this->{$options['local_key']});
    $result = $options['class']::find($query);

    return $result;
  }

  public function many_to_many($model, $options = array())
  {
    if (!class_exists($model))
      $model = self::get('namespace') . '\\' . Inflector::classify($model);
    $classes = array(self::get('table'), Helpers::tableize($model));
    sort($classes);

    $defaults = array(
      "class" => $model,
      "local_key" => 'id',
      "foreign_key" => 'id',
      "join_table" => join('_',$classes),
      "join_table_this_key" => Helpers::propertize(self::get('model')) . "_id",
      "join_table_that_key" => Helpers::propertize($model) . "_id",
      "target_table" => Helpers::tableize($model),
    );
    $options += $defaults;

    $sql = "SELECT {$options['target_table']}.*
            FROM {$options['target_table']}
            JOIN {$options['join_table']} 
              ON {$options['join_table']}.{$options['join_table_that_key']} 
                = {$options['target_table']}.{$options['foreign_key']}
              AND {$options['join_table']}.{$options['join_table_this_key']}
                ={$this->{$options['local_key']}}";
    $result = $options['class']::sql($sql);
    
    return $result;
  }

  public static function sql($sql, $bound_params = array())
  {
    $query = Freeform::$db->prepare($sql);
    $query->execute($bound_params);
    $results = $query->fetchAll(\Pdo::FETCH_CLASS, self::get('class'));

    return $results;
  }
}