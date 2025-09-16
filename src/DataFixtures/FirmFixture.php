<?php

namespace App\DataFixtures;

use App\Entity\Firms;
use App\Entity\FirmUserProfiles;
use App\Entity\States;
use App\Entity\StoragePlans;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


class FirmFixture extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        $now = new \DateTime();

        // ag: set up firm
        $firm1 = new Firms();

        $firm1->setName('Abel Accountants')
            ->setAddr1('200 W. Braddock Rd')
            ->setCity('Alexandria')
            ->setState('VA')
            ->setZip('22302')
            ->setCountry('USA')
            ->setPhone('703-548-3008')
            ->setAccount('abelaccountants')
            ->setQbbRemovalNum(180)
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setStoragePlan($this->getReference('professional_plan', StoragePlans::class));

        // ag: add reference for later use in FirmUserProfileFixture

        $manager->persist($firm1);
        $this->addReference('firm1', $firm1);

        // ag: set up user that for firmUserProfile
        // $user = new User();
        // $user->setEmail('agarrido84+firm@gmail.com')
        //     ->setRoles(['ROLE_FIRM'])
        //     ->setCreatedDate($now)
        //     ->setUpdatedDate($now)
        //     ->setIsActive(true);

        // $hashedPassword = $this->passwordHasher->hashPassword($user, 'supersecret');
        // $user->setPassword($hashedPassword);

        // $manager->persist($user);


        // ag: set up firmUserProfile
        // $firmUserProfile = new FirmUserProfiles();
        // $firmUserProfile->setFirstName('Abel')
        //     ->setLastName('Garrido')
        //     ->setTitle('CFO')
        //     ->setPhone('703-548-3008 x100')
        //     ->setBulkAction(true)
        //     ->setSeeAllFiles(true)
        //     ->setContactUser(true)
        //     ->setUserType('primary')
        //     ->setCreatedDate($now)
        //     ->setUpdatedDate($now)
        //     ->setFirm($firm)
        //     ->setUser($user);

        // $manager->persist($firmUserProfile);

        $manager->flush();
    }

    // Tell Doctrine this fixture depends on StateFixture
    public function getDependencies(): array
    {
        return [
            PlanFixture::class,
            StateFixture::class,
        ];
    }
}
