<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as BaseUser;

class User extends BaseUser
{
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }
}
