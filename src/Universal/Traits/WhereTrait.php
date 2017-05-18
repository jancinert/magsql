<?php

namespace Magsql\Universal\Traits;

use Magsql\Driver\BaseDriver;
use Magsql\ArgumentArray;
use Magsql\Universal\Syntax\Conditions;
use InvalidArgumentException;

trait WhereTrait
{
    protected $where;

    /**
     * The arguments here are always binding to varibles, won't be deflated to sql query.
     *
     * Example:
     *
     *     where('name = :name', [ 'name' => 'name' ]);
     */
    public function where($expr = null, array $args = array())
    {
        if (!$this->where) {
            $this->where = new Conditions([], $this);
        }
        if ($expr) {
            if (is_string($expr)) {
                $this->where->raw($expr, $args);
            } elseif (is_array($expr)) {
                foreach ($expr as $key => $val) {
                    $this->where->equal($key, $val);
                }
            } else {
                throw new InvalidArgumentException("Unsupported argument type of 'where' method.");
            }
        }

        return $this->where;
    }

    public function setWhere(Conditions $where)
    {
        $where->setParent($this);
        $this->where = $where;
    }

    public function getWhere()
    {
        if ($this->where) {
            return $this->where;
        }

        return $this->where = new Conditions([], $this);
    }

    public function buildWhereClause(BaseDriver $driver, ArgumentArray $args)
    {
        if ($this->where && count($this->where->exprs)) {
            return ' WHERE '.$this->where->toSql($driver, $args);
        }

        return '';
    }
}
