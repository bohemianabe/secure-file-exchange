<?php

// ag: test entity persistences specifically on Firms, User, FirmUserProfiles
namespace App\Tests;

use App\Entity\Firms;
use App\Entity\User;
use App\Entity\FirmUserProfiles;
use App\Entity\StoragePlans;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FirmUserProfileEntityEntryTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    public function testCreateFirmUserProfile(): void
    {

        $now = new DateTime();

        $storagePlan = $this->entityManager
            ->getRepository(StoragePlans::class)
            ->findOneBy(['name' => 'Professional']);

        // 1. Create Firm
        $firm = new Firms();
        $firm->setName('Test Firm');
        $firm->setAccount('testfirm123');
        $firm->setStoragePlan($storagePlan);
        $firm->setAddr1('200 W. Braddock Rd');
        $firm->setCity('Alexandria');
        $firm->setState('VA');
        $firm->setZip('22202');
        $firm->setPhone('303-999-6683');
        $firm->setActive(true);
        $firm->setCreatedDate($now);
        $firm->setUpdatedDate($now);

        // 2. Create User
        $user = new User();
        $user->setEmail('johndoe@example.com');
        $user->setPassword('testpassword123');
        $user->setRoles(['ROLE_FIRM']);
        $user->setIsActive(true);
        $user->setCreatedDate($now);
        $user->setUpdatedDate($now);

        // 3. Create FirmUserProfile tied to both
        $profile = new FirmUserProfiles();
        $profile->setFirstName('John');
        $profile->setLastName('Doe');
        $profile->setTitle('CEO');
        $profile->setPhone('123-456-7890');
        $profile->setUserType('primary');
        $profile->setBulkAction(true);
        $profile->setSeeAllFiles(true);
        $profile->setContactUser(true);
        $profile->setCreatedDate($now);
        $profile->setUpdatedDate($now);

        // set relationships
        $profile->setFirm($firm);
        $profile->setUser($user);

        // Persist all entities
        $this->entityManager->persist($firm);
        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Assert they were created with IDs
        $this->assertNotNull($firm->getId());
        $this->assertNotNull($user->getId());
        $this->assertNotNull($profile->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
    }
}
