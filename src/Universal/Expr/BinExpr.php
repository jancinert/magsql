<?php

namespace Magsql\Universal\Expr;

use Magsql\Driver\BaseDriver;
use Magsql\ToSqlInterface;
use Magsql\ArgumentArray;

class BinExpr implements ToSqlInterface
{
    public $op;

    public $operand;

    public $operand2;

    public function __construct($operand, $op, $operand2)
    {
        $this->op = $op;
        $this->operand = $operand;
        $this->operand2 = $operand2;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        return $this->operand.' '.$this->op.' '.$driver->deflate($this->operand2, $args);
    }

    public static function __set_state($array)
    {
        return new self($array['operand'], $array['op'], $array['operand2']);
    }
}
