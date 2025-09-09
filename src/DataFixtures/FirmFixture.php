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

        // ag: set up the storage plans
        // $storagePlan1 = new StoragePlans();

        // $storagePlan1->setName('professional')->setStorage('10240.00')->setPrice('25.50')->setCreatedDate($now)->setUpdatedDate($now);
        // $manager->persist($storagePlan1);

        // $storagePlan2 = new StoragePlans();
        // $storagePlan2->setName('platinum')->setStorage('51200.00')->setPrice('55.50')->setCreatedDate($now)->setUpdatedDate($now);
        // $manager->persist($storagePlan2);

        // $storagePlan3 = new StoragePlans();
        // $storagePlan3->setName('platinum plus')->setStorage('102400.00')->setPrice('100.00')->setCreatedDate($now)->setUpdatedDate($now);
        // $manager->persist($storagePlan3);

        // ag: set up firm
        $firm = new Firms();

        $firm->setName('Abel Accountants')
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

        $manager->persist($firm);

        // ag: set up user that for firmUserProfile
        $user = new User();
        $user->setEmail('agarrido84+firm@gmail.com')
            ->setRoles(['ROLE_FIRM'])
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'supersecret');
        $user->setPassword($hashedPassword);

        $manager->persist($user);


        // ag: set up firmUserProfile
        $firmUserProfile = new FirmUserProfiles();
        $firmUserProfile->setFirstName('Abel')
            ->setFirstName('Garrido')
            ->setTitle('CFO')
            ->setPhone('703-548-3008 x100')
            ->setBulkAction(true)
            ->setSeeAllFiles(true)
            ->setUserType('primary')
            ->setCreatedDate($now)
            ->setUpdatedDate($now)
            ->setFirm($firm)
            ->setUser($user);

        $manager->persist($firmUserProfile);

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
