<?php

namespace Freeform\Query;

use Freeform\Freeform;
use Pdo;

class Select extends AbstractQuery
{
  private $fields = array();

  private $return_one = false;

  public function __construct($class = null)
  {
    $this->class = $class;
  }

  public function from($table)
  {
    $this->table = $table;

    return $this;
  }

  protected function buildSql()
  {
    $parts = array();

    $parts[] = "Select";
    $parts[] = join(',', $this->fields) ?: '*';
    $parts[] = "FROM {$this->table}";
    if ($this->where)
      $parts[] = "WHERE " . join(' AND ', $this->where);
    $parts[] = join("\n", $this->options);

    $sql = join("\n", $parts);

    return $sql;
  }


  public function return_one($return_one = true)
  {
    $this->return_one = $return_one;

    return $this;
  }

  public function execute()
  {
    $sql = $this->buildSql();
    
    $query = Freeform::$db->prepare($sql);
    $query->execute($this->bound_params);
    $results = $query->fetchAll(Pdo::FETCH_CLASS, $this->class);

    if ($this->return_one)
      $results = $results ? $results[0] : false;

    return $results;
  }
}