<?php

namespace App\Entity;

use App\Repository\FirmUserProfilesRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: FirmUserProfilesRepository::class)]
class FirmUserProfiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', type: 'string', length: 64, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 64, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'title', type: 'string', length: 64, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(name: 'phone', type: 'string', length: 36, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'bulk_action', type: 'boolean', nullable: true, options: ['default' => true])]
    private ?bool $bulkAction = null;

    #[ORM\Column(name: 'see_all_files', type: 'boolean', nullable: true, options: ['default' => true])]
    private ?bool $seeAllFiles = null;

    // ag: user could be primary, admin accountant, employee
    #[ORM\Column(name: 'user_type', type: 'string', length: 64, nullable: true)]
    private ?string $userType = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;

    // ag: connects MANY firmUserProfiles TO their ONE designated firm
    #[ORM\ManyToOne(targetEntity: Firms::class, inversedBy: "firmUserProfiles", cascade: ["persist"])]
    #[ORM\JoinColumn(name: 'firm_id', referencedColumnName: 'id')]
    private ?Firms $firm = null;

    // ag: connects One firmUserProfile TO their Many clientUserProfiles
    #[ORM\OneToMany(targetEntity: ClientUserProfiles::class, mappedBy: "firmUserProfiles")]
    #[ORM\JoinColumn(name: 'firm_id', referencedColumnName: 'id')]
    private ?Collection $clientUserProfiles = null;

    // 1–1 with User (firm user) points to user name w/ email and password
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone($phone): static
    {
        $this->phone = $phone;

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

    public function getBulkAction(): ?bool
    {
        return $this->bulkAction;
    }
    public function setBulkAction(int $bulkAction): static
    {
        $this->bulkAction = $bulkAction;

        return $this;
    }

    public function getSeeAllFiles(): ?bool
    {
        return $this->seeAllFiles;
    }

    public function setSeeAllFiles(bool $seeAllFiles): static
    {
        $this->seeAllFiles;

        return $this;
    }

    public function getUserType(): ?string
    {
        return $this->userType;
    }
    public function setUserType(string $userType): static
    {
        $this->userType;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFirm(): ?Firms
    {
        return $this->firm;
    }

    public function setFirm(Firms $firm): static
    {
        $this->firm = $firm;

        return $this;
    }
}
