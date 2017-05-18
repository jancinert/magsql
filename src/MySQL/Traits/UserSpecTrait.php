<?php

namespace Magsql\MySQL\Traits;

use Magsql\MySQL\Syntax\UserSpecification;

trait UserSpecTrait
{
    public $userSpecifications = array();

    public function user($spec = null)
    {
        $user = null;
        if (is_string($spec) && $user = UserSpecification::createWithFormat($this, $spec)) {
            $this->userSpecifications[] = $user;
        } else {
            $this->userSpecifications[] = $user = new UserSpecification($this);
        }

        return $user;
    }
}
