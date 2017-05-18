<?php

namespace Magsql;

/**
 * ParamMarker is a data type for parameter mark without binding 
 * value.
 *
 * Used for question mark and named mark
 */
class ParamMarker
{
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function getMarker()
    {
        return '?';
    }
}
