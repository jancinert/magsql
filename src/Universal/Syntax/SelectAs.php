<?php

namespace Magsql\Universal\Syntax;

use Magsql\ToSqlInterface;
use Magsql\Driver\BaseDriver;
use Magsql\ArgumentArray;
use InvalidArgumentException;

class SelectAs implements ToSqlInterface
{
    protected $expr;

    protected $as;

    public function __construct($expr, $as)
    {
        $this->expr = $expr;
        $this->as = $as;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = '';

        if (is_string($this->expr)) {
            $sql .= $this->expr;
        } elseif ($this->expr instanceof ToSqlInterface) {
            $sql .= $this->expr->toSql($driver, $args);
        } else {
            throw new InvalidArgumentException('Unknown type expr');
        }
        $sql .= ' AS '.$driver->quoteIdentifier($this->as);

        return $sql;
    }
}
