<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Security $security): Response
    {
        // ag: check if user is already logged in
        if ($security->getUser()) {
            // Redirect to post-login role-aware route
            return $this->redirectToRoute('app_post_login');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    // ag: handles the logic afer a successful login
    #[Route('/post-login', name: 'app_post_login')]
    public function postLogin(Security $security): Response
    {
        // dd($this->isGranted('ROLE_ADMIN'));
        if (!$security->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute(match (true) {
            // ag: find user role and direct them to their dashboard
            $this->isGranted('ROLE_ADMIN')  => 'admin_dashboard',
            $this->isGranted('ROLE_FIRM')   => 'firm_dashboard',
            $this->isGranted('ROLE_CLIENT') => 'client_dashboard',
            // default                         => 'app_login',
        });
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
