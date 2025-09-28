<?php

// ag: edge case for a the add new firm modal on the admin dashboard.
namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminAddNewFirmModalTest extends WebTestCase
{
    private KernelBrowser $client;

    // protected function setUP(): void
    // {
    //     $this->client = static::createClient();
    // }
    public function testAddNewFirmSuccess(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $container = static::getContainer();

        $user = $container->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'agarrido84+admin@gmail.com']);

        // $crawler = $client->request('GET', '/login');

        // $form = $crawler->selectButton('Sign in')->form([
        //     '_username' => 'agarrido84+admin@gmail.com',
        //     '_password' => 'supersecret',
        // ]);
        // $client->submit($form);
        // Get the session service properly in test env
        // $session = self::getContainer()->get('session.factory')->createSession();
        // $session->start();

        // Create the token and put it in the session
        // $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        // $session->set('_security_main', serialize($token));
        // $session->save();

        // Inject the session cookie into the client
        // $client->getCookieJar()->set(
        //     new Cookie($session->getName(), $session->getId())
        // );


        // Now the client is logged in
        // $crawler = $client->request('GET', '/admin/dashboard');
        // $this->assertResponseIsSuccessful();
        // $this->assertSelectorExists('#adminDashboardViewTable');

        // create and set session into the client’s container
        // $session = new Session(new MockFileSessionStorage());
        // $session->start();

        // $session->set('_security_main', serialize(new UsernamePasswordToken(
        //     $user,
        //     'main',
        //     ['ROLE_ADMIN']
        // )));
        // $session->save();

        // Inject session cookie into the client
        // $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));


        $client->loginUser($user, 'main');
        // navigate to dashboard (optional check)
        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#adminDashboardViewTable');

        $link = $crawler->filter('a.btn.btn-primary')->link();
        // dd($link);
        $crawler = $client->click($link);

        $this->assertSelectorExists('#adminAddFirmModal');

        // Symfony redirects to /post-login (your config)
        // $this->assertResponseRedirects('/post-login');

        // Follow redirect and land on dashboard
        // $crawler = $client->followRedirect();

        // Assert that /post-login redirected further to /admin/dashboard
        // $this->assertResponseRedirects('/admin/dashboard');

        // ag: i had to follow the redirect again because i have logic in login to redirect user to dashboard if logged in. So they can't see the login page WHILE logged in.
        // $crawler = $client->followRedirect();

        // $this->assertStringContainsString('/admin/dashboard', $client->getRequest()->getUri());
        // $this->assertSelectorExists('#adminDashboardViewTable'); // adjust to your dashboard page content
        // dd([
        //     'status' => $client->getResponse()->getStatusCode(),
        //     'uri' => $client->getRequest()->getUri(),
        //     'content' => $client->getResponse()->getContent(),
        // ]);


        // $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);
        // dd($client);
        // Simulate a POST request with firm, user, and firmUserProfile data
        // $client->request('POST', '/admin/ajax/new-firm-submit', [
        // 'firm' => [
        //     'name' => 'Test Firm',
        //     'account' => 'testfirm123',
        //     'storagePlan' => '42',
        //     'addr1' => '200 W. Braddock Rd',
        //     'addr2' => '',
        //     'city' => 'Alexandria',
        //     'state' => 'VA',
        //     'zip' => '22202',
        //     'phone' => '303-999-6683',
        //     'active' => '1',
        //     // '_token' => $csrfTokenManager->getToken('firm')->getValue(),
        //     'logo' => null, // skip file upload for now
        // ],
        // 'user' => [
        //     'email' => 'johndoe@example.com',
        //     'password' => 'testpassword123',
        //     'roles' => ['ROLE_FIRM'],
        //     // '_token' => $csrfTokenManager->getToken('user')->getValue(),
        // ],
        // 'firm_user_profile' => [
        //     'firstName' => 'John',
        //     'lastName' => 'Doe',
        //     'title' => 'CEO',
        //     'phone' => '123-456-7890',
        //     'userType' => 'primary',
        //     'bulkAction' => true,
        //     'seeAllFiles' => true,
        //     'contactUser' => true,
        //     // '_token' => $csrfTokenManager->getToken('firm_user_profile')->getValue(),
        // ],
        // ]);

        // $this->assertResponseIsSuccessful();
        // $this->assertResponseHeaderSame('content-type', 'application/json');

        // $data = json_decode($client->getResponse()->getContent(), true);

        // $this->assertTrue($data['success']);
        // $this->assertSame(
        //     'New Firm and Primary User Successfully Added, along with Email.',
        //     $data['message']
        // );
    }

    // public function testAddNewFirmFailsWithoutData(): void
    // {
    //     $client = static::createClient();

    //     // Send empty POST data
    //     $client->request('POST', '/admin/ajax/new-firm-submit', []);

    //     $this->assertResponseIsSuccessful();
    //     $this->assertResponseHeaderSame('content-type', 'application/json');

    //     $data = json_decode($client->getResponse()->getContent(), true);

    //     $this->assertFalse($data['success']);
    //     $this->assertNotEmpty($data['errors']);
    // }
}
