<?php

namespace App\Entity;

use App\Repository\AdminUserProfilesRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AdminUserProfilesRepository::class)]
class AdminUserProfiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_admin_user_profile', nullable: false, type: 'integer')]
    private ?int $idAdminUserProfile = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'first_name', type: 'string', length: 64, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: 'string', length: 64, nullable: true)]
    private ?string $lastName = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;


    public function getId(): ?int
    {
        return $this->idAdminUserProfile;
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

    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(?string $n): static
    {
        $this->firstName = $n;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(?string $n): static
    {
        $this->lastName = $n;
        return $this;
    }

    public function getUserFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getCreatedDate(): ?\DateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTime $date)
    {

        // Truncate minutes and seconds (only keep year-month-day-hour)
        $this->createdDate = $date;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTime
    {
        return $this->updatedDate;
    }

    public function setUpdatedDate(\DateTime $date)
    {

        // Truncate minutes and seconds (only keep year-month-day-hour)
        $this->updatedDate = $date;

        return $this;
    }
}
