<?php

namespace Magsql\Universal\Syntax;

use Magsql\ArgumentArray;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\ToSqlInterface;
use BadMethodCallException;
use Magsql\MySQL\Traits\IndexHintTrait;

class Join implements ToSqlInterface
{
    use IndexHintTrait;

    public $conditions;

    public $alias;

    protected $joinType;

    public function __construct($table, $alias = null, $joinType = null)
    {
        $this->table = $table;
        $this->alias = $alias;
        $this->joinType = $joinType;
        $this->conditions = new Conditions();
    }

    public function left()
    {
        $this->joinType = 'LEFT';

        return $this;
    }

    public function right()
    {
        $this->joinType = 'RIGHT';

        return $this;
    }

    public function inner()
    {
        $this->joinType = 'INNER';

        return $this;
    }

    public function on($conditionExpr = null, array $args = array())
    {
        if (is_string($conditionExpr)) {
            $this->conditions->raw($conditionExpr, $args);
        }

        return $this->conditions;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = '';

        if ($this->joinType) {
            $sql .= ' '.$this->joinType;
        }

        $sql .= ' JOIN '.$this->table;

        if ($this->alias) {
            $sql .= ' AS '.$this->alias;
        }

        if ($driver instanceof MySQLDriver) {
            $sql .= $this->buildIndexHintClause($driver, $args);
        }

        if ($this->conditions->hasExprs()) {
            $sql .= ' ON ('.$this->conditions->toSql($driver, $args).')';
        }

        return $sql;
    }

    public function _as($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    public function __call($m, $a)
    {
        if ($m == 'as') {
            return $this->_as($a[0]);
        }
        throw new BadMethodCallException("Invalid method call: $m");
    }
}
