<?php

namespace App\DataFixtures;

use App\Entity\AdminUserProfiles;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();
        // Create a User entry
        $user = new User();
        $user->setEmail('agarrido84+admin@gmail.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedDate($now);
        $user->setUpdatedDate($now);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'supersecret');
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        // Create the AdminUserProfile entry and link to User
        $profile = new AdminUserProfiles();
        $profile->setUser($user);
        $profile->setCreatedDate($now);
        $profile->setUpdatedDate($now);
        $profile->setFirstName("Abel");
        $profile->setLastName("Admin");

        $manager->persist($profile);

        $manager->flush();
    }
}
