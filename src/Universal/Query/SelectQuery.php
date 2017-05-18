<?php

namespace Magsql\Universal\Query;

use Exception;
use Magsql\Driver\BaseDriver;
use Magsql\Driver\MySQLDriver;
use Magsql\ToSqlInterface;
use Magsql\ArgumentArray;
use Magsql\Universal\Syntax\Conditions;
use Magsql\Universal\Traits\OrderByTrait;
use Magsql\Universal\Traits\WhereTrait;
use Magsql\Universal\Traits\PagingTrait;
use Magsql\Universal\Expr\SelectExpr;
use Magsql\MySQL\Traits\PartitionTrait;
use Magsql\MySQL\Traits\IndexHintTrait;
use Magsql\Universal\Traits\JoinTrait;
use Magsql\Universal\Traits\OptionTrait;

/**
 * SQL Builder for generating CRUD SQL.
 *
 * @code
 *
 *  $select = new Magsql\Universal\Query\SelectQuery;
 *  $sqlbuilder->select(array(
 *      'foo',
 *      'bar',
 *  ));
 *  $sql = $select->toSql($driver, $args);
 *
 * @code
 *
 * The fluent interface rules of Query objects
 *
 *    1. setters should return self, since there is no return value.
 *    2. getters should be just what they are.
 *    3. modifier can set / append data and return self
 */
class SelectQuery implements ToSqlInterface
{
    use OrderByTrait;
    use WhereTrait;
    use JoinTrait;
    use PartitionTrait;
    use OptionTrait;
    use IndexHintTrait;
    use PagingTrait;

    protected $select = array();

    protected $from = array();

    protected $having;

    protected $groupByList = array();

    protected $groupByModifiers = array();

    protected $lockModifier;

    protected $rollupModifier;

    public function __construct()
    {
        $this->having = new Conditions();
    }

    /**********************************************************
     * Accessors
     **********************************************************/

    public function all()
    {
        return $this->option('ALL');
    }

    public function distinct()
    {
        return $this->option('DISTINCT');
    }

    public function distinctRow()
    {
        return $this->option('DISTINCTROW');
    }

    /********************************************************
     * MySQL Only Options
     *
     * @see http://dev.mysql.com/doc/refman/5.7/en/select.html
     *******************************************************/
    public function useSqlCache()
    {
        return $this->option('SQL_CACHE');
    }

    public function useSqlNoCache()
    {
        return $this->option('SQL_NO_CACHE');
    }

    public function useSmallResult()
    {
        return $this->option('SQL_SMALL_RESULT');
    }

    public function useBigResult()
    {
        return $this->option('SQL_BIG_RESULT');
    }

    public function useBufferResult()
    {
        return $this->option('SQL_BUFFER_RESULT');
    }

    public function select($select)
    {
        if (is_array($select)) {
            $this->select = $this->select + $select;
        } else {
            $this->select = $this->select + func_get_args();
        }

        return $this;
    }

    public function setSelect($select)
    {
        if (is_array($select)) {
            $this->select = $select;
        } else {
            $this->select = func_get_args();
        }

        return $this;
    }

    public function getSelect()
    {
        return $this->select;
    }

    /**
     * ->from('posts', 'p')
     * ->from('users', 'u').
     */
    public function from($table, $alias = null)
    {
        if ($alias) {
            $this->from[$table] = $alias;
        } else {
            $this->from[] = $table;
        }

        return $this;
    }

    public function setFrom($table)
    {
        if (is_array($table)) {
            $this->from = $table;
        } else {
            $this->from = func_get_args();
        }

        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function having($expr = null, array $args = array())
    {
        if (is_string($expr)) {
            $this->having->raw($expr, $args);
        }

        return $this->having;
    }

    /**
     * Functions support GROUP BY.
     *
     *  > SELECT FROM_DAYS(SUM(TO_DAYS(date_col))) FROM tbl_name;
     *
     * @see http://dev.mysql.com/doc/refman/5.7/en/group-by-functions.html
     * @see http://dev.mysql.com/doc/refman/5.7/en/group-by-functions-and-modifiers.html
     */
    public function groupBy($expr, array $modifiers = null)
    {
        if (is_array($expr)) {
            $this->groupByList = array_merge($this->groupByList, $expr);
        } else {
            $this->groupByList[] = $expr;
        }
        if ($modifiers) {
            $this->groupByModifiers = $modifiers;
        }

        return $this;
    }

    public function removeGroupBy()
    {
        $this->groupByList = array();
    }

    /**
     * Note: SELECT FOR UPDATE does not work when used in select statement with a subquery.
     */
    public function forUpdate()
    {
        $this->lockModifier = 'FOR UPDATE';

        return $this;
    }

    public function lockInShareMode()
    {
        $this->lockModifier = 'LOCK IN SHARE MODE';
    }

    public function rollup()
    {
        $this->rollupModifier = 'WITH ROLLUP';
    }

    /****************************************************************
     * Builders
     ***************************************************************/
    public function buildSelectClause(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = ' ';
        $cols = array();
        $first = true;
        foreach ($this->select as $k => $v) {
            if ($first) {
                $first = false;
            } else {
                $sql .= ', ';
            }

            /* "column AS alias" OR just "column" */
            if (is_integer($k)) {
                if ($v instanceof SelectExpr || $v instanceof ToSqlInterface) {
                    $sql .= $v->toSql($driver, $args);
                } elseif (is_array($v)) {
                    $sql .= implode(' ', $v);
                } else {
                    $sql .= $v;
                }
            } else {
                $sql .= $k.' AS '.$v;
            }
        }

        return $sql;
    }

    protected function buildFromClauseMySQL(BaseDriver $driver, ArgumentArray $args)
    {
        $tableRefs = array();
        foreach ($this->from as $k => $v) {
            /* "column AS alias" OR just "column" */
            if (is_string($k)) {
                $sql = $driver->quoteTable($k).' AS '.$v;
                if ($this->definedIndexHint($v)) {
                    $sql .= $this->buildIndexHintClauseByTableRef($v, $driver, $args);
                } elseif ($this->definedIndexHint($k)) {
                    $sql .= $this->buildIndexHintClauseByTableRef($k, $driver, $args);
                }
                $tableRefs[] = $sql;
            } elseif (is_integer($k) || is_numeric($k)) {
                $sql = $driver->quoteTable($v);
                if ($this->definedIndexHint($v)) {
                    $sql .= $this->buildIndexHintClauseByTableRef($v, $driver, $args);
                }
                $tableRefs[] = $sql;
            }
        }
        if (!empty($tableRefs)) {
            return ' FROM '.implode(', ', $tableRefs);
        }

        return '';
    }

    protected function buildFromClause(BaseDriver $driver, ArgumentArray $args)
    {
        $tableRefs = array();
        foreach ($this->from as $k => $v) {
            /* "column AS alias" OR just "column" */
            if (is_string($k)) {
                $tableRefs[] = $driver->quoteTable($k).' AS '.$v;
            } elseif (is_integer($k) || is_numeric($k)) {
                $tableRefs[] = $driver->quoteTable($v);
            }
        }
        if (!empty($tableRefs)) {
            return ' FROM '.implode(', ', $tableRefs);
        }

        return '';
    }

    public function buildGroupByClause(BaseDriver $driver, ArgumentArray $args)
    {
        if (empty($this->groupByList)) {
            return '';
        }

        // TODO: group by modifiers, currently only support for syntax like "GROUP BY a WITH ROLLUP".
        // @see http://dev.mysql.com/doc/refman/5.7/en/group-by-modifiers.html
        $sql = ' GROUP BY '.implode(', ', $this->groupByList);
        if ($this->groupByModifiers) {
            $sql .= ' '.implode(' ', $this->groupByModifiers);
        }

        if ($this->rollupModifier) {
            if (!$driver instanceof MySQLDriver) {
                throw new Exception('Incompatible Query Usage: rollup is only supported in MySQL.');
            }
            $sql .= ' '.$this->rollupModifier;
        }

        return $sql;
    }

    public function buildLockModifierClauseMySQL()
    {
        if ($this->lockModifier) {
            return ' '.$this->lockModifier;
        }

        return '';
    }

    public function buildHavingClause(BaseDriver $driver, ArgumentArray $args)
    {
        if (!empty($this->having->exprs)) {
            return ' HAVING '.$this->having->toSql($driver, $args);
        }

        return '';
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args)
    {
        if ($driver instanceof MySQLDriver) {
            return 'SELECT'
                .$this->buildOptionClause()
                .$this->buildSelectClause($driver, $args)
                .$this->buildFromClauseMySQL($driver, $args)
                .$this->buildPartitionClause($driver, $args)
                .$this->buildJoinClause($driver, $args)
                .$this->buildWhereClause($driver, $args)
                .$this->buildGroupByClause($driver, $args)
                .$this->buildHavingClause($driver, $args)
                .$this->buildOrderByClause($driver, $args)
                .$this->buildPagingClause($driver, $args)
                .$this->buildLockModifierClauseMySQL()
                ;
        }

        return 'SELECT'
            .$this->buildOptionClause()
            .$this->buildSelectClause($driver, $args)
            .$this->buildFromClause($driver, $args)
            .$this->buildJoinClause($driver, $args)
            .$this->buildWhereClause($driver, $args)
            .$this->buildGroupByClause($driver, $args)
            .$this->buildHavingClause($driver, $args)
            .$this->buildOrderByClause($driver, $args)
            .$this->buildPagingClause($driver, $args)
            ;
    }

    public function __clone()
    {
        $this->having = $this->having;
        $this->where = $this->where;
    }
}
