<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: "email", type: "string", length: 180, nullable: false, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(name: "roles", type: "json", nullable: false)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(name: 'password', type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private ?bool $isActive;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $createdDate;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_date', type: 'datetime', options: ['default' => "CURRENT_TIMESTAMP"])]
    private ?\DateTime $updatedDate;

    // ag: to make to the correct profile
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: FirmUserProfiles::class, cascade: ['persist', 'remove'])]
    private ?FirmUserProfiles $firmUserProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ClientUserProfiles::class, cascade: ['persist', 'remove'])]
    private ?ClientUserProfiles $clientUserProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: AdminUserProfiles::class, cascade: ['persist', 'remove'])]
    private ?AdminUserProfiles $adminUserProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower($email);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     * ag: the email will be the unique identifier for a user
     * 
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * ag: get user type there are currently 3 Admin, firm user or a client user
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $status)
    {
        $this->isActive = $status;

        return $this;
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

    // ag: this returns which of the profiles the user is associated with.
    public function getUserProfile(): AdminUserProfiles|FirmUserProfiles|ClientUserProfiles|null
    {
        if ($this->adminUserProfile !== null) {
            return $this->adminUserProfile;
        }

        if ($this->firmUserProfile !== null) {
            return $this->firmUserProfile;
        }

        if ($this->clientUserProfile !== null) {
            return $this->clientUserProfile;
        }

        return null; // no profile attached
    }
}
