<?php

namespace Freeform\Query;

use Freeform\Freeform;
use Pdo;

class Delete extends AbstractQuery
{

  public function __construct($table)
  {
    $this->table = $table;

    return $this;
  }

  protected function buildSql()
  {
    $parts = array();
    $conditions = array();

    foreach($this->fields as $field)
    {
      $conditions[] = "$field = ?";
    }

    $parts[] = "DELETE FROM $this->table";
    $parts[] = "WHERE";
    $parts[] = join(' AND ', $conditions);

    $sql = join("\n", $parts);

    return $sql;
  }

  public function fields($fields)
  {
    $this->fields = $fields;

    return $this;
  }

  public function values($values)
  {
    $this->values = $values;

    return $this;
  }

  public function execute()
  {
    $sql = $this->buildSql();

    $query = Freeform::$db->prepare($sql);
    
    return $query->execute($this->bound_params);
  }
}