<?php

namespace App\Entity;

use App\Repository\StoragePlansRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: StoragePlansRepository::class)]
class StoragePlans
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_storage_plan', nullable: false, type: 'integer')]
    private ?int $idStoragePlan = null;

    #[ORM\Column(name: 'name', type: 'string', length: 64, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(name: 'storage', type: Types::DECIMAL, precision: 8, scale: 2, nullable: false)]
    private ?string $storage = null;

    #[ORM\Column(name: 'price', type: Types::DECIMAL, precision: 8, scale: 2, nullable: false)]
    private ?string $price = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;

    // convenience denormalization if you want fast scoping; optional
    #[ORM\OneToMany(mappedBy: 'storagePlans', targetEntity: Firms::class)]
    private Collection $firms;

    public function getId(): ?int
    {
        return $this->idStoragePlan;
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

    public function getStorage(): ?string
    {
        return $this->storage;
    }

    public function setStorage(string $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }
    public function setCreatedDate(\DateTime $createdDate): static
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getUpdatedDate(): ?\DateTime
    {
        return $this->updatedDate;
    }
    public function setUpdatedDate(\DateTime $updatedDate): static
    {
        $this->updatedDate = $updatedDate;
        return $this;
    }
}
