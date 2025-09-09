<?php

namespace App\DataFixtures;

use App\Entity\StoragePlans;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;


class PlanFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $now = new \DateTime();

        // ag: set up the storage plans
        $storagePlan1 = new StoragePlans();

        $storagePlan1->setName('professional')->setStorage('10240.00')->setPrice('25.50')->setCreatedDate($now)->setUpdatedDate($now);
        $manager->persist($storagePlan1);

        $storagePlan2 = new StoragePlans();
        $storagePlan2->setName('platinum')->setStorage('51200.00')->setPrice('55.50')->setCreatedDate($now)->setUpdatedDate($now);
        $manager->persist($storagePlan2);

        $storagePlan3 = new StoragePlans();
        $storagePlan3->setName('platinum plus')->setStorage('102400.00')->setPrice('100.00')->setCreatedDate($now)->setUpdatedDate($now);
        $manager->persist($storagePlan3);

        // ag: add reference for later use in FirmFixture
        $this->addReference('professional_plan', $storagePlan1);

        $manager->flush();
    }
}
