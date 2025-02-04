<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity('email', entityClass: User::class)]
#[UniqueEntity('username', entityClass: User::class)]
class Register
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $username;
    #[Assert\NotBlank]
    #[Assert\PasswordStrength]
    public string $plainPassword;
}