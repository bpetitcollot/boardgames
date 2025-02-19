<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '"user"')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $id;
    #[ORM\Column(length: 255)]
    private string $email;
    #[ORM\Column(length: 255)]
    #[Groups('public-view')]
    private string $username;
    #[ORM\Column(length: 255)]
    private string $password;
    #[ORM\Column]
    private bool $enabled = true;
    #[ORM\Column(type: Types::JSON)]
    private array $roles;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups('public-view')]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastLoginAt;

    public function __construct(string $email, string $username)
    {
        $this->id = uniqid();
        $this->email = $email;
        $this->username = $username;
        $this->roles = [self::ROLE_USER];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTime $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function promote(string $role): self
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function demote(string $role): self
    {
        $this->roles = array_filter($this->roles, function ($r) use ($role) {
            return $r !== $role;
        });
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }
    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }


}