<?php
use Magsql\Universal\Expr\BinExpr;

class BinExprTest extends \PHPUnit\Framework\TestCase
{
    public function testBinExprVarExport()
    {
        $expr = new BinExpr(1, '+', 20);
        $code = 'return ' . var_export($expr, true) . ';';
        $ret = eval($code); 
        $this->assertInstanceOf('Magsql\Universal\Expr\BinExpr', $ret);
        $this->assertEquals('+', $ret->op);
    }
}

