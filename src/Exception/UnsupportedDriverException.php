<?php

namespace Magsql\Exception;

use RuntimeException;
use Magsql\Driver\BaseDriver;

class UnsupportedDriverException extends RuntimeException
{
    public $driver;

    public $caller;

    public function __construct(BaseDriver $driver, $caller)
    {
        $this->driver = $driver;
        $this->caller = $caller;
        parent::__construct(get_class($driver).' is not supported for '.get_class($this->caller));
    }
}
