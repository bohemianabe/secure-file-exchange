<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginPageTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();

        // Request login page
        $client->request('GET', '/login');

        // Page should load
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // form is present
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'agarrido84+admin@gmail.com',
            '_password' => 'supersecret',
        ]);
        $client->submit($form);

        // Symfony redirects to /post-login (your config)
        $this->assertResponseRedirects('/post-login');

        // Follow redirect and land on dashboard
        $crawler = $client->followRedirect();

        // Assert that /post-login redirected further to /admin/dashboard
        $this->assertResponseRedirects('/admin/dashboard');

        // ag: i had to follow the redirect again because i have logic in login to redirect user to dashboard if logged in. So they can't see the login page WHILE logged in.
        $crawler = $client->followRedirect();

        $this->assertStringContainsString('/admin/dashboard', $client->getRequest()->getUri());
        $this->assertSelectorExists('#adminDashboardViewTable'); // adjust to your dashboard page content
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'wrong@example.test',
            '_password' => 'badpass',
        ]);
        $client->submit($form);

        // Should redirect back to login
        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        // Look for your error flash
        $this->assertSelectorExists('.alert-danger');
    }
}
