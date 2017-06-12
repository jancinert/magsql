<?php

namespace Magsql\Driver;

use Magsql\Raw;
use Magsql\DataType\Unknown;
use Magsql\ArgumentArray;
use Magsql\ParamMarker;
use Magsql\Bind;
use Magsql\ToSqlInterface;
use DateTime;
use Exception;
use LogicException;
use PDO;

abstract class BaseDriver
{
    /**
     * Question mark parameter marker.
     *
     * (?,?)
     */
    const QMARK_PARAM_MARKER = 1;

    /**
     * Named parameter marker.
     */
    const NAMED_PARAM_MARKER = 2;

    public $alwaysBindValues = false;

    public $paramNameCnt = 1;

    public $paramMarkerType = self::NAMED_PARAM_MARKER;

    public $quoteTable;

    /**
     * String quoter handler.
     *  
     *  Array:
     *
     *    array($obj,'method')
     */
    public $quoter;

    protected $conn;

    public function __construct(PDO $conn = null)
    {
        $this->conn = $conn;
    }

    public function setQuoter(callable $quoter)
    {
        $this->quoter = $quoter;
    }

    public function alwaysBindValues($on = true)
    {
        $this->alwaysBindValues = $on;
    }

    /**
     * @param bool $enable
     */
    public function setQuoteTable($enable = true)
    {
        $this->quoteTable = $enable;
    }

    // The SQL statement can contain zero or more named (:name) or question mark (?) parameter markers
    public function setNamedParamMarker()
    {
        $this->paramMarkerType = self::NAMED_PARAM_MARKER;
    }

    public function setQMarkParamMarker()
    {
        $this->paramMarkerType = self::QMARK_PARAM_MARKER;
    }

    abstract public function quoteIdentifier($id);

    /**
     * Check driver option to quote column name.
     *
     * column quote can be configured by 'quote_column' option.
     *
     * @param string $name column name
     * @return string column name with/without quotes.
     */
    public function quoteColumn($name)
    {
        // TODO: quote for DB.TABLE.COLNAME
        if (preg_match('/\W/', $name)) {
            return $name;
        }
        return $this->quoteIdentifier($name);
    }

    /**
     * Check driver optino to quote table name.
     *
     * column quote can be configured by 'quote_table' option.
     *
     * @param string $name table name
     *
     * @return string table name with/without quotes.
     */
    public function quoteTable($name)
    {
        if ($this->quoteTable) {
            // TODO: Split DB.Table
            return $this->quoteIdentifier($name);
        }

        return $name;
    }

    /**
     * quote & escape string with single quote.
     *
     * quote functions for different platform
     *
     *    string mysqli_real_escape_string ( mysqli $link , string $escapestr )
     *    string pg_escape_string ([ resource $connection ], string $data )
     *    string PDO::quote ( string $string [, int $parameter_type = PDO::PARAM_STR ] )
     */
    public function quote($string)
    {
        if ($this->quoter) {
            return call_user_func($this->quoter, $string);
        }

        // Defualt escape function, this is not safe.
        return "'".addslashes($string)."'";
    }

    public function allocateBind($value)
    {
        return new Bind('p'.$this->paramNameCnt++, $value);
    }

    public function deflateScalar($value)
    {
        if ($value === null) {
            return 'NULL';
        } elseif ($value === true) {
            return 'TRUE';
        } elseif ($value === false) {
            return 'FALSE';
        } elseif (is_integer($value) || is_float($value)) {
            return ''.$value;
        } elseif (is_string($value)) {
            return $this->quote($value);
        } else {
            throw new Exception("Can't deflate value, unknown type.");
        }
    }

    public function cast($value)
    {
        if ($value instanceof DateTime) {
            // return $value->format(DateTime::ISO8601);
            return $value->format(DateTime::ATOM);
        }

        return $value;
    }

    /**
     * For variable placeholder like PDO, we need 1 or 0 for boolean type,.
     *
     * For pgsql and mysql sql statement, 
     * we use TRUE or FALSE for boolean type.
     *
     * FOr sqlite sql statement:
     * we use 1 or 0 for boolean type.
     */
    public function deflate($value, ArgumentArray $args = null)
    {
        if ($this->alwaysBindValues) {
            if ($value instanceof Raw) {
                return $value->__toString();
            } elseif ($value instanceof Bind) {
                if ($args) {
                    $args->bind($value);
                }

                return $value->getMarker();
            } elseif ($value instanceof ParamMarker) {
                if ($args) {
                    $args->bind(new Bind($value->getMarker(), null));
                }

                return $value->getMarker();
            } else {
                $bind = $this->allocateBind($value);
                if ($args) {
                    $args->bind($bind);
                }

                return $bind->getMarker();
            }
        }

        if ($value === null) {
            return 'NULL';
        } elseif ($value === true) {
            return 'TRUE';
        } elseif ($value === false) {
            return 'FALSE';
        } elseif (is_integer($value)) {
            return intval($value);
        } elseif (is_float($value)) {
            return floatval($value);
        } elseif (is_string($value)) {
            return $this->quote($value);
        } elseif (is_callable($value)) {
            return call_user_func($value);
        } elseif (is_object($value)) {
            if ($value instanceof Bind) {
                if ($args) {
                    $args->bind($value);
                }

                if ($this->paramMarkerType === self::QMARK_PARAM_MARKER) {
                    return '?';
                }
                /*
                elseif ($this->paramMarkerType === self::NAMED_PARAM_MARKER) {
                    return $value->getMarker();
                }
                */
                return $value->getMarker();
            } elseif ($value instanceof ParamMarker) {
                if ($args) {
                    $args->bind(new Bind($value->getMarker(), null));
                }

                if ($this->paramMarkerType === self::QMARK_PARAM_MARKER) {
                    return '?';
                }
                /*
                else if ($this->paramMarkerType === self::NAMED_PARAM_MARKER) {
                    return $value->getMarker();
                }
                */
                return $value->getMarker();
            } elseif ($value instanceof Unknown) {
                return 'UNKNOWN';
            } elseif ($value instanceof DateTime) {

                // convert DateTime object into string
                // return $this->quote($value->format(DateTime::ISO8601));
                return $this->quote($value->format(DateTime::ATOM)); // sqlite use ATOM format
            } elseif ($value instanceof ToSqlInterface) {
                return $value->toSql($this, $args);
            } elseif ($value instanceof Raw) {
                return $value->__toString();
            } else {
                throw new LogicException('Unsupported class: '.get_class($value));
            }
        } elseif (is_array($value)) {
            // error_log("LazyRecord: deflating array type value", 0);
            return $value[0];
        } else {
            throw new LogicException('BaseDriver::deflate: Unsupported variable type');
        }

        return $value;
    }
}
