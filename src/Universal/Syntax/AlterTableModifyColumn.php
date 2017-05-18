<?php

namespace Magsql\Universal\Syntax;

use Magsql\ToSqlInterface;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\ArgumentArray;
use Magsql\Exception\UnsupportedDriverException;
use Magsql\Exception\IncompleteSettingsException;

class AlterTableModifyColumn implements ToSqlInterface
{
    protected $column;

    protected $after;

    protected $first;

    public function __construct(Column $column)
    {
        $this->column = $column;
    }

    public function after($column)
    {
        if ($column instanceof Column) {
            $this->after = $column->getName();
        } else {
            $this->after = $column;
        }

        return $this;
    }

    public function first()
    {
        $this->first = true;

        return $this;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = '';
        if ($driver instanceof MySQLDriver) {
            $sql = 'MODIFY COLUMN ';
            if (!$this->column->getType()) {
                throw new IncompleteSettingsException('Missing column type on column `'.$this->column->name.'`');
            }
            $sql .= $this->column->buildDefinitionSqlForModify($driver, $args);

            if ($this->after) {
                $sql .= ' AFTER '.$driver->quoteIdentifier($this->after);
            } elseif ($this->first) {
                $sql .= ' FIRST';
            }
        } elseif ($driver instanceof PgSQLDriver) {

            // ALTER TABLE distributors RENAME CONSTRAINT zipchk TO zip_check;
            $sql = 'ALTER COLUMN ';
            $sql .= $driver->quoteIdentifier($this->column->getName());

            if ($type = $this->column->getType()) {
                $sql .= ' TYPE '.$type;
            } elseif ($default = $this->column->default) {
                $sql .= ' SET DEFAULT '.$driver->deflate($default);
            } elseif ($this->column->nullDefined()) {
                if ($this->column->notNull) {
                    $sql .= ' SET NOT NULL';
                } else {
                    $sql .= ' DROP NOT NULL';
                }
            } else {
                throw new IncompleteSettingsException('Empty column attribute ');
            }
        } else {
            throw new UnsupportedDriverException($driver, $this);
        }

        return $sql;
    }
}
