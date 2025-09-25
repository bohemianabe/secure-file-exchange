<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAddNewFirmModalTest extends WebTestCase
{
    public function testAddNewFirmSuccess(): void
    {
        $client = static::createClient();

        // Simulate a POST request with firm, user, and firmUserProfile data
        $client->request('POST', '/admin/ajax/new-firm-submit', [
            'firm' => [
                'name' => 'Test Firm',
                'account' => 'testfirm123',
                'active' => '1',
                'logo' => null, // skip file upload for now
            ],
            'user' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'johndoe@example.com',
                'password' => 'testpassword123',
            ],
            'firm_user_profile' => [
                'title' => 'CEO',
                'phone' => '123-456-7890',
                'userType' => 'primary',
                'bulkAction' => true,
                'seeAllFiles' => true,
                'contactUser' => true,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertSame(
            'New Firm and Primary User Successfully Added, along with Email.',
            $data['message']
        );
    }

    public function testAddNewFirmFailsWithoutData(): void
    {
        $client = static::createClient();

        // Send empty POST data
        $client->request('POST', '/admin/ajax/new-firm-submit', []);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['errors']);
    }
}
