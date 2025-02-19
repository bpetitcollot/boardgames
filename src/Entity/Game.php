<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $id;
    #[ORM\Column(length: 255)]
    private string $name;
    #[ORM\Column(length: 255)]
    private Boardgame $type;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy;
    #[ORM\Column(type: Types::SMALLINT)]
    private int $numberOfPlayers;
    #[ORM\Column(type: Types::JSON)]
    private array $state;
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: "game", cascade: ['remove'])]
    private Collection $players;

    public function __construct(Boardgame $type, string $name, int $numberOfPlayers)
    {
        $this->id = uniqid();
        $this->type = $type;
        $this->name = $name;
        $this->numberOfPlayers = $numberOfPlayers;
        $this->players = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->state = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): Boardgame
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNumberOfPlayers(): int
    {
        return $this->numberOfPlayers;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function setNumberOfPlayers(int $numberOfPlayers): void
    {
        $this->numberOfPlayers = $numberOfPlayers;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): void
    {
        $this->state = $state;
    }

    public function getPlayers(): Collection
    {
        return $this->players;
    }

}