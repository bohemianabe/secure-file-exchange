<?php

namespace App\Entity;

use App\Repository\ClientUserProfilesRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClientUserProfilesRepository::class)]
class ClientUserProfiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_client_user_profile', nullable: false, type: 'integer')]
    private ?int $idClientUserProfile = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 64, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 64, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'title', type: 'string', length: 64, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 36, nullable: true)]
    private ?string $phone = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;

    // ag: connects the MANY clientUserProfile to ONE client-orgs
    #[ORM\ManyToOne(targetEntity: Clients::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id_client', nullable: false, onDelete: 'RESTRICT')]
    private Clients $client;

    // the firm user who owns/manages this client user
    #[ORM\ManyToOne(targetEntity: FirmUserProfiles::class)]
    #[ORM\JoinColumn(name: 'firm_user_profile_id', referencedColumnName: 'id_firm_user_profile', nullable: false, onDelete: 'RESTRICT')]
    private FirmUserProfiles $firmUserProfile;

    // convenience denormalization if you want fast scoping; optional
    #[ORM\ManyToOne(targetEntity: Firms::class)]
    #[ORM\JoinColumn(name: 'firm_id', referencedColumnName: 'id_firm', nullable: false, onDelete: 'RESTRICT')]
    private Firms $firm;

    // 1–1 with User (firm user) points to user name w/ email and password
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;


    public function getId(): ?int
    {
        return $this->idClientUserProfile;
    }

    public function getFirmName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone($phone): static
    {
        $this->phone = $phone;

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
