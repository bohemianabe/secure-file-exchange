<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminAddNewFirmModalTest extends WebTestCase
{
    public function testAddNewFirmSuccess(): void
    {
        $client = static::createClient();

        // Create a mock session
        $session = new Session(new MockFileSessionStorage());
        $request = new Request();
        $request->setSession($session);

        // Push the request with the session onto the RequestStack
        /** @var RequestStack $requestStack */
        $requestStack = static::getContainer()->get(RequestStack::class);
        $requestStack->push($request);

        $user = static::getContainer()
            ->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => 'fixtureadmin@example.test']);

        $container = static::getContainer();
        // $session = self::getContainer()->get('session.factory')->createSession();
        // $session->start();
        // log in this user
        $client->loginUser($user, 'main');
        // $client->disableReboot();

        // dd($client);
        // 1) token present?
        $token = $container->get('security.token_storage')->getToken();
        // dd($token);
        dd([
            'has_token'   => $token !== null,
            'user'        => $token?->getUser()?->getUserIdentifier(),
            'roles'       => $token?->getRoleNames(),
            // 2) session present / written by the firewall?
            'session_started' => $container->has('session') ? $container->get('session')->isStarted() : false,
            'has_security_key' => $container->has('session') ? $container->get('session')->has('_security_main') : false,
        ]);

        // $crawler = $client->request('POST', '/admin/ajax/new-firm-submit');
        // ag: used to generate a token for the forms
        $csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);

        // dd($client);
        // Simulate a POST request with firm, user, and firmUserProfile data
        $client->request('POST', '/admin/ajax/new-firm-submit', [
            'firm' => [
                'name' => 'Test Firm',
                'account' => 'testfirm123',
                'storagePlan' => '42',
                'addr1' => '200 W. Braddock Rd',
                'addr2' => '',
                'city' => 'Alexandria',
                'state' => 'VA',
                'zip' => '22202',
                'phone' => '303-999-6683',
                'active' => '1',
                '_token' => $csrfTokenManager->getToken('firm')->getValue(),
                'logo' => null, // skip file upload for now
            ],
            'user' => [
                'email' => 'johndoe@example.com',
                'password' => 'testpassword123',
                'roles' => ['ROLE_FIRM'],
                '_token' => $csrfTokenManager->getToken('user')->getValue(),
            ],
            'firm_user_profile' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'title' => 'CEO',
                'phone' => '123-456-7890',
                'userType' => 'primary',
                'bulkAction' => true,
                'seeAllFiles' => true,
                'contactUser' => true,
                '_token' => $csrfTokenManager->getToken('firm_user_profile')->getValue(),
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
