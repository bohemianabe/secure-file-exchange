<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SetPassWordController extends AbstractController
{
    use Traits\EntityActions;

    #[Route('/set-password/{token}', name: 'app_set_password')]
    public function index(string $token, Request $request, ?User $user, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['firstLoginToken' => $token]);
        // dd($user);

        if (!$user || !$user->isFirstLoginTokenValid($token)) {
            $this->addFlash('danger', 'Invalid or expired link. Please contact an administrator or try the Forget Password link.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $plainPassword = $request->request->get('password');

            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
                $user->setFirstLoginToken(null);
                $user->setFirstLoginTokenExpiresAt(null);

                $this->em->flush();

                $this->addFlash('success', 'Password set! You can log in now.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('set_pass_word/index.html.twig', [
            'controller_name' => 'SetPassWordController',
        ]);
    }
}
