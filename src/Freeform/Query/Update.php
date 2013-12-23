<?php

namespace Freeform\Query;

use Freeform\Freeform;

class Update extends AbstractQuery
{

  public function __construct($table)
  {
    $this->table = $table;

    return $this;
  }

  protected function buildSql()
  {
    $parts = array();

    $parts[] = "REPLACE INTO $this->table";
    $parts[] = "(`" . join('`, `', $this->fields) . "`)";
    $parts[] = "VALUES";
    $parts[] = "(" . join(', ', $this->values) . ")";


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
