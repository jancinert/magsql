<?php

namespace Magsql\Universal\Query;

use Magsql\ToSqlInterface;
use Magsql\ArgumentArray;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\Driver\PgSQLDriver;
use Magsql\Exception\CriticalIncompatibleUsageException;
use Magsql\Exception\IncompleteSettingsException;
use Magsql\Exception\UnsupportedDriverException;
use Magsql\PgSQL\Traits\ConcurrentlyTrait;

/**
 SELECT * FROM points.
 */
class CreateIndexQuery implements ToSqlInterface
{
    use ConcurrentlyTrait;

    protected $type;

    protected $options = array();

    protected $method;

    protected $name;

    protected $tableName;

    protected $columns;

    protected $storageParameters = array();

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * MySQL, PostgreSQL.
     */
    public function unique($name = null)
    {
        $this->type = 'UNIQUE';
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * FULLTEXT is only supported on MySQL.
     *
     * MySQL only
     */
    public function fulltext($name = null)
    {
        $this->type = 'FULLTEXT';
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * MySQL only.
     */
    public function spatial($name = null)
    {
        $this->type = 'SPATIAL';
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * MySQL: {BTREE | HASH}
     * PostgreSQL:  {btree | hash | gist | spgist | gin}.
     */
    public function using($method)
    {
        $this->method = $method;

        return $this;
    }

    public function create($name)
    {
        $this->name = $name;

        return $this;
    }

    public function on($tableName, array $columns = array())
    {
        $this->tableName = $tableName;
        if (!empty($columns)) {
            $this->columns = $columns;
        }

        return $this;
    }

    public function with($name, $val)
    {
        $this->storageParameters[$name] = $val;

        return $this;
    }

    protected function buildMySQLQuery(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = 'CREATE';

        if ($this->type) {
            // validate index type
            $sql .= ' '.$this->type;
        }

        $sql .= ' INDEX';

        $sql .= ' '.$driver->quoteIdentifier($this->name).' ON '.$driver->quoteIdentifier($this->tableName);

        if (!empty($this->columns)) {
            $sql .= ' ('.implode(',', $this->columns).')';
        }
        if ($this->method) {
            $sql .= ' USING '.$this->method;
        }

        return $sql;
    }

    protected function buildPgSQLQuery(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = 'CREATE';

        if ($this->type === 'UNIQUE') {
            $sql .= ' UNIQUE';
        } elseif ($this->type && $this->type === 'UNIQUE') {
            throw new CriticalIncompatibleUsageException();
        }

        $sql .= ' INDEX';

        $sql .= $this->buildConcurrentlyClause($driver, $args);

        $sql .= ' '.$driver->quoteIdentifier($this->name).' ON '.$driver->quoteIdentifier($this->tableName);

        // TODO: validate method 
        if ($this->method) {
            $sql .= ' USING '.$this->method;
        }
        if (!empty($this->columns)) {
            $sql .= ' ('.implode(',', $this->columns).')';
        }

        if (!empty($this->storageParameters)) {
            $sql .= ' WITH ';
            foreach ($this->storageParameters as $name => $val) {
                $sql .= $name.' = '.$val.',';
            }
            $sql = rtrim($sql, ',');
        }
        // TODO: support tablespace and predicate
        return $sql;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        if (!$this->tableName) {
            throw new IncompleteSettingsException('CREATE INDEX Query requires tableName');
        }
        if ($driver instanceof PgSQLDriver) {
            return $this->buildPgSQLQuery($driver, $args);
        } elseif ($driver instanceof MySQLDriver) {
            return $this->buildMySQLQuery($driver, $args);
        } else {
            throw new UnsupportedDriverException($driver, $this);
        }
    }
}
