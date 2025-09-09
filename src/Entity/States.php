<?php

namespace App\Entity;

use App\Repository\StatesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatesRepository::class)]
class States
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_state', nullable: false, type: 'integer')]
    private ?int $idState = null;

    #[ORM\Column(length: 2, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->idState;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
