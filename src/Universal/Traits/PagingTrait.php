<?php

namespace Magsql\Universal\Traits;

use Magsql\Driver\BaseDriver;
use Magsql\ArgumentArray;

trait PagingTrait
{
    public $limit;

    public $offset;

    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function page($page, $pageSize = 10)
    {
        if ($page > 1) {
            $this->offset(($page - 1) * $pageSize);
        }

        return $this->limit($pageSize);
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function buildPagingClause(BaseDriver $driver, ArgumentArray $args)
    {
        $sql = '';
        if ($this->limit) {
            $sql .= ' LIMIT '.$this->limit;
        }
        if ($this->offset) {
            $sql .= ' OFFSET '.$this->offset;
        }

        return $sql;
    }
}
