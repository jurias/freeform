<?php

namespace Freeform\Query;

use Pdo;
use Freeform\Freeform;

abstract class AbstractQuery
{
  protected $table;
  protected $where = array();
  protected $bound_params = array();
  protected $options = array();

  public function where($statement)
  {
    $this->where[] = $statement;

    return $this;
  }

  public function bind($params)
  {
    $params = (array) $params;

    foreach($params as $param)
      $this->bound_params[] = $param;

    return $this;
  }

  protected function options($option)
  {
    $this->options[] = $option;
   
    return $this;
  }

  public function limit($value)
  {
    $this->options[] = "LIMIT $value";

    return $this;
  }

  abstract protected function buildSql();

  abstract protected function execute();

}