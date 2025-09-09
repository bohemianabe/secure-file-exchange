<?php

namespace App\Entity;

use App\Repository\ClientsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClientsRepository::class)]
class Clients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_client', nullable: false, type: 'integer')]
    private ?int $idClient = null;

    #[ORM\Column(name: 'name', type: 'string', length: 128)]
    private ?string $name = null;

    #[ORM\Column(name: 'addr_1', type: 'string', length: 64, nullable: true)]
    private ?string $addr1 = null;

    #[ORM\Column(name: 'addr_2', type: 'string', length: 64, nullable: true)]
    private ?string $addr2 = null;

    #[ORM\Column(name: 'city', type: 'string', length: 64, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(name: 'state', type: 'string', length: 16, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(name: 'zip', type: 'string', length: 64, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(name: 'country', type: 'string', length: 64, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 28, nullable: true)]
    private ?string $phone = null;

    // ag: type could be a company/org or independent client
    #[ORM\Column(name: 'organizational_type', type: 'integer', nullable: false)]
    private ?int $organizationalType = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: true, options: ['default' => true])]
    private ?bool $active = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;

    #[ORM\OneToOne(targetEntity: Firms::class)]
    #[ORM\JoinColumn(name: 'firm_id', referencedColumnName: 'id_firm', nullable: false, onDelete: 'RESTRICT')]
    private Firms $firm;

    // ag: connects One client TO their Many clientUserProfiles
    #[ORM\OneToMany(targetEntity: ClientUserProfiles::class, mappedBy: "clients")]
    private ?Collection $clients = null;

    public function getId(): ?int
    {
        return $this->idClient;
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

    public function getAddr1(): ?string
    {
        return $this->addr1;
    }
    public function setAddr1(string $addr1): static
    {
        $this->addr1 = $addr1;
        return $this;
    }

    public function getAddr2(): ?string
    {
        return $this->addr2;
    }
    public function setAddr2(string $addr2): static
    {
        $this->addr2 = $addr2;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }
    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
    public function setState(?string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }
    public function setZip(string $zip): static
    {
        $this->zip = $zip;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }
    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getOrganizationalType(): ?int
    {
        return $this->organizationalType;
    }
    public function setOrganizationalType(int $organizationalType): static
    {
        $this->organizationalType = $organizationalType;
        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }
    public function setActive(bool $active): static
    {
        $this->active = $active;
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
