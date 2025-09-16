<?php

namespace App\DataFixtures;

use App\Entity\Firms;
use App\Entity\FirmUserProfiles;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class FirmUserProfileFixture extends Fixture implements DependentFixtureInterface
{

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime();

        // set user as they go in tandem with a profile
        $user = new User();
        $user->setEmail('agarrido84+firm1@gmail.com')
            ->setRoles(['ROLE_FIRM'])
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'supersecret');
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        $user2 = new User();
        $user2->setEmail('agarrido84+firm2@gmail.com')
            ->setRoles(['ROLE_FIRM'])
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setIsActive(true);

        $hashedPassword2 = $this->passwordHasher->hashPassword($user2, 'supersecret');
        $user->setPassword($hashedPassword2);

        $manager->persist($user2);

        $user3 = new User();
        $user3->setEmail('agarrido84+firm3@gmail.com')
            ->setRoles(['ROLE_FIRM'])
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setIsActive(true);

        $hashedPassword3 = $this->passwordHasher->hashPassword($user3, 'supersecret');
        $user->setPassword($hashedPassword3);

        $manager->persist($user3);


        // ag: set up firmUserProfile
        $firmUserProfile = new FirmUserProfiles();
        $firmUserProfile->setFirstName('Abel')
            ->setLastName('Garrido')
            ->setTitle('CFO')
            ->setPhone('703-548-3008 x100')
            ->setBulkAction(true)
            ->setSeeAllFiles(true)
            ->setContactUser(true)
            ->setUserType('primary')
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setFirm($this->getReference('firm1', Firms::class))
            ->setUser($user);

        $manager->persist($firmUserProfile);

        $firmUserProfile2 = new FirmUserProfiles();
        $firmUserProfile2->setFirstName('Jayden')
            ->setLastName('Daniels')
            ->setTitle('Office Supervisor')
            ->setPhone('999-888-9988 x100')
            ->setBulkAction(true)
            ->setSeeAllFiles(true)
            ->setContactUser(true)
            ->setUserType('admin')
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setFirm($this->getReference('firm1', Firms::class))
            ->setUser($user2);

        $manager->persist($firmUserProfile2);

        $firmUserProfile3 = new FirmUserProfiles();
        $firmUserProfile3->setFirstName('Dan')
            ->setLastName('Quinn')
            ->setTitle('Head Coach')
            ->setPhone('111-222-9988 x400')
            ->setBulkAction(true)
            ->setSeeAllFiles(true)
            ->setContactUser(true)
            ->setUserType('employee')
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setFirm($this->getReference('firm1', Firms::class))
            ->setUser($user3);

        $manager->persist($firmUserProfile3);

        $manager->flush();
    }
    // Tell Doctrine this fixture depends on FirmFixture
    public function getDependencies(): array
    {
        return [
            FirmFixture::class,
        ];
    }
}
