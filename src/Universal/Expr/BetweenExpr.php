<?php

namespace Magsql\Universal\Expr;

use Magsql\Driver\BaseDriver;
use Magsql\ToSqlInterface;
use Magsql\ArgumentArray;

/**
 * http://dev.mysql.com/doc/refman/5.0/en/comparison-operators.html#operator_between.
 */
class BetweenExpr implements ToSqlInterface
{
    public $exprStr;

    public $min;

    public $max;

    public function __construct($exprStr, $min, $max)
    {
        $this->exprStr = $exprStr;
        $this->min = $min;
        $this->max = $max;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        return $this->exprStr.' BETWEEN '.$driver->deflate($this->min, $args).' AND '.$driver->deflate($this->max, $args);
    }

    public static function __set_state($array)
    {
        return new self($array['exprStr'], $array['min'], $array['max']);
    }
}
