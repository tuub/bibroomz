<?php

namespace App\Library\Traits;

trait UUIDIsPrimaryKey
{
    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
