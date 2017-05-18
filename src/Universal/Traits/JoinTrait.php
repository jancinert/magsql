<?php

namespace Magsql\Universal\Traits;

use Magsql\Driver\BaseDriver;
use Magsql\ArgumentArray;
use Magsql\Universal\Syntax\Join;
use Magsql\Universal\Syntax\LeftJoin;
use Magsql\Universal\Syntax\RightJoin;

trait JoinTrait
{
    protected $joins = array();

    public function innerJoin($table, $alias = null)
    {
        $join = new Join($table, $alias, 'INNER');
        $this->joins[] = $join;

        return $join;
    }

    public function rightJoin($table, $alias = null)
    {
        $join = new Join($table, $alias, 'RIGHT');
        $this->joins[] = $join;

        return $join;
    }

    public function leftJoin($table, $alias = null)
    {
        $join = new Join($table, $alias, 'LEFT');
        $this->joins[] = $join;

        return $join;
    }

    public function join($table, $alias = null, $joinType = null)
    {
        $join = new Join($table, $alias, $joinType);
        $this->joins[] = $join;

        return $join;
    }

    public function getJoins()
    {
        return $this->joins;
    }

    public function buildJoinClause(BaseDriver $driver, ArgumentArray $args)
    {
        if (empty($this->joins)) {
            return '';
        }
        $sql = '';
        foreach ($this->joins as $join) {
            $sql .= $join->toSql($driver, $args);
        }
        return $sql;
    }
}
