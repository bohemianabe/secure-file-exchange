<?php

namespace App\Controller;

use App\Entity\Firms;
use App\Entity\FirmUserProfiles;
use App\Entity\States;
use App\Entity\StoragePlans;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\FirmType;
use App\Form\FirmUserProfileType;
use App\Form\UserType;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\SluggerInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[IsGranted("ROLE_ADMIN")]
class AdminUserAccessController extends AbstractController
{
    use Traits\EntityActions;

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(Request $request): Response
    {
        // ag: calculate data for the circle charts on page
        // dd($this->_getStoragePlanMetrics());

        // dd($this->_fetchWhere(Firms::class, ['active' => true]));
        return $this->render('admin_user_access/dashboard.html.twig', [
            'firms' => $this->_fetchAll(Firms::class),
            'storage_plans' => $this->_fetchAll(StoragePlans::class),
            'states' => $this->_fetchAll(States::class),
            'firm_user_types' => $this->firmUserTypes,
            // ag: variable to pass in needed modals for the dashboard page.
            'modals_to_include' => ['adminAddNewFirm.html.twig'],
            'chartData' => ['activeFirmMetrics' => $this->_getActiveFirmMetrics(), 'storagePlanMetrics' => $this->_getStoragePlanMetrics()],
        ]);
    }

    #[Route('/admin/firm_view/{firm}', name: 'admin_firm_view')]
    public function adminFirmViewPage(Request $request, Firms $firm): Response
    {
        // dd($firm->getFirmUserProfiles());
        return $this->render('admin_user_access/firmView.html.twig', [
            'firm' => $firm,
            'storage_plans' => $this->_fetchAll(StoragePlans::class),
            'states' => $this->_fetchAll(States::class),
            'modals_to_include' => ['adminToggleFirmStatus.html.twig', 'defaultTemplate.html.twig']
        ]);
    }

    #[Route('/admin/firm_user/new/{firmId}/modal', name: 'admin_new_firm_user_modal')]
    public function adminNewFirmUserProfileModal(Request $request, $firmId)
    {
        $firm = $this->em->getRepository(Firms::class)->find($firmId);
        if ($request->get('_route') == 'admin_new_firm_user_modal') {
            return $this->render('layouts/modals/adminAddNewFirmUserProfile.html.twig', [
                'firm' => $firm,
            ]);
        }
    }

    #[Route('/admin/firm_user/{firmUserProfileId}/modal', name: "admin_firm_user_modal", methods: ['GET', 'POST'])]
    #[Route('/admin/firm_user/modal/{firmUserProfileId}/submit', name: "admin_firm_user_modal_submit")]
    public function adminEditFirmUserProfileModal(Request $request, $firmUserProfileId): Response
    {
        $firmUserProfile = $this->em->getRepository(FirmUserProfiles::class)->find($firmUserProfileId);
        $user = $firmUserProfile->getUser();
        if ($request->get('_route') == 'admin_firm_user_modal') {

            return $this->render('layouts/modals/adminEditFirmUserProfile.html.twig', [
                'firmUserProfile' => $firmUserProfile,
            ]);
        } else {
            $now = new \DateTime();

            if ($user->getEmail() !== $request->request->all('user')['email']) {
                // ag: update email address
                $user->setEmail($request->request->all('user')['email']);
                $user->setUpdatedDate($now);

                $this->em->persist($user);
                $this->em->flush($user);
            }

            $firmForm = $this->createForm(FirmUserProfileType::class, $firmUserProfile);
            $firmForm->handleRequest($request);

            if ($firmForm->isSubmitted() && $firmForm->isValid()) {
                // ag: manually set updatedDate. 
                $firmUserProfile->setUpdatedDate($now);
                $this->em->persist($firmUserProfile);
                $this->em->flush($firmUserProfile);

                $this->addFlash('success', 'Firm User Updated.');
                return $this->redirect($request->headers->get('referer'));
                // return new JsonResponse(['success' => true, 'message' => 'went through']);
            } else {
                $this->addFlash('error', 'Form invalid. Contact an administrator.');
                return $this->redirect($request->headers->get('referer'));
            }
        }
    }

    #[Route('/admin/deactivate_firm/{firm}', name: 'admin_toggle_firm_status')]
    public function adminTogglerFirmStatus(Request $request, ?Firms $firm)
    {
        $now = new \DateTime();
        if ($firm->getActive()) {
            $firm->setActive(false);
        } else {
            $firm->setActive(true);
        }
        $firm->setUpdatedDate($now);
        $this->em->flush($firm);

        return $this->redirect($request->headers->get('referer'));
    }

    //*
    //
    // ag: ********************************************** ajax calls below here **********************************
    //
    //*
    #[Route('/admin/ajax/new-firm-submit', name: 'admin_new_firm_submit', methods: ['GET', 'POST'])]
    public function adminNewFirmSubmit(Request $request, SluggerInterface $slugger, ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer): Response
    {
        // dd($request->request);
        // ag: empty error array to collect errors while processing new firm and users
        $errors = array();
        $now = new \DateTime();
        // ag: process the new firm
        $newFirm = new Firms();

        $firmForm = $this->createForm(FirmType::class, $newFirm);
        $firmForm->handleRequest($request);

        if ($firmForm->isSubmitted() && $firmForm->isValid()) {

            // ag: upload the logo
            $logoFile = $firmForm->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('firm_logo_public_path'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // dd($e->getMessage());
                    // Handle exception / log
                    $errors[] = [
                        'error_type' => 'Firm logo upload error.',
                        'error_message' => $e->getMessage(),
                    ];
                }

                $newFirm->setLogo($newFilename);
            }

            $newFirm->setCreatedDate($now);
            $newFirm->setUpdatedDate($now);
            $this->em->persist($newFirm);
            $this->em->flush($newFirm);

            // ag: if firm was created then go on to add the firmUserProfile
            if ($newFirm) {
                // ag: created the new user

                // dd($request->request);
                $newUser = new User();
                $newUserForm = $this->createForm(UserType::class, $newUser);
                $newUserForm->handleRequest($request);

                if ($newUserForm->isSubmitted() && $newUserForm->isValid()) {
                    $newUser->setCreatedDate($now);
                    $newUser->setUpdatedDate($now);

                    // ag: add the firstLogin tokens to be used below to send welcome email
                    $newUserToken = ByteString::fromRandom(32)->toString();
                    $newUser->setFirstLoginToken($newUserToken);
                    $newUser->setFirstLoginTokenExpiresAt(new \DateTimeImmutable('+7 days'));

                    $this->em->persist($newUser);
                    $this->em->flush($newUser);
                } else {
                    $errors[] = [
                        'error_type' => 'New User Form Error',
                        'error_message' => iterator_to_array($newUserForm->getErrors(true, true)),
                    ];
                }

                if ($newUser) {
                    // ag: create firmUserProfile
                    $newFirmUserProfile = new FirmUserProfiles();
                    $newFirmUserProfile->setUser($newUser);
                    $newFirmUserProfile->setFirm($newFirm);

                    $firmUserProfileForm = $this->createForm(FirmUserProfileType::class, $newFirmUserProfile);
                    $firmUserProfileForm->handleRequest($request);

                    // dd(iterator_to_array($firmUserProfileForm->getErrors(true, true)));
                    if ($firmUserProfileForm->isSubmitted() && $firmUserProfileForm->isValid()) {
                        $newFirmUserProfile->setCreatedDate($now);
                        $newFirmUserProfile->setUpdatedDate($now);

                        $this->em->persist($newFirmUserProfile);
                        $this->em->flush($newFirmUserProfile);

                        // ag: once newfirmUserProfile is create send out email to set password
                        try {
                            $email = (new TemplatedEmail())
                                ->from($this->params->get('from_email'))
                                ->to($newUser->getEmail())
                                ->subject($this->params->get('app_name') . '*****' . 'New User Password Setup' . '*****')
                                ->htmlTemplate('emails/newUserWelcomeEmail.html.twig')
                                ->textTemplate('emails/newUserWelcomeEmail.txt.twig')
                                ->context([
                                    'user' => $newUser,
                                    'setPasswordUrl' => $this->generateUrl('app_set_password', [
                                        'token' => $newUser->getFirstLoginToken(),
                                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                                    'tokenLifetime' => '7 days',
                                ]);

                            $mailer->send($email);
                        } catch (FileException $e) {
                            $errors[] = [
                                'error_type' => 'New User Welcome Email Failure',
                                'error_message' => $e->getMessage(),
                            ];
                        }
                    } else {
                        $errors[] = [
                            'error_type' => 'New Firm User Profile Form Error',
                            'error_message' => iterator_to_array($firmUserProfileForm->getErrors(true, true)),
                        ];
                    }
                    // dd($firmUserProfileForm);
                }
            }
        } else {
            $errors[] = [
                'error_type' => 'New Firm Form Error',
                'error_message' => iterator_to_array($firmForm->getErrors(true, true)),
            ];
        }

        if (empty($errors)) {
            $data = [
                'success' => true,
                'message' => 'New Firm and Primary User Successfully Added, along with Email.',
            ];
        } else {
            $data = [
                'success' => false,
                'errors' => $errors,
            ];
        }
        // ag: return response
        return new JsonResponse($data);
    }

    #[Route('/admin/ajax/update-firm-data', name: 'admin_ajax_update_firm_data')]
    public function adminUpdateFirmData(Request $request, SluggerInterface $slugger): Response
    {
        // dd($request->request);
        $errors = array();
        // ag: process the new firm
        $firmId = $request->request->get('firmId') ?? null;

        if ($firmId) {
            $updatedFirm = $this->em->getRepository(Firms::class)->find($firmId);
        }

        $firmForm = $this->createForm(FirmType::class, $updatedFirm);
        $firmForm->handleRequest($request);

        if ($firmForm->isSubmitted() && $firmForm->isValid()) {

            // ag: remove old logo file if it exist
            $oldLogo = $updatedFirm->getLogo();
            if ($oldLogo && $firmForm->get('logo')->getData()) {
                $oldLogoPath = $this->getParameter('firm_logo_public_path') . '/' . $oldLogo;
                if (file_exists($oldLogoPath)) {
                    unlink($oldLogoPath);
                }
            }

            // ag: upload the logo
            $logoFile = $firmForm->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('firm_logo_public_path'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception / log
                    $errors[] = [
                        'error_message' => 'Firm logo upload error: ' . $e->getMessage(),
                    ];
                }

                $updatedFirm->setLogo($newFilename);
            }

            // ag: I don't need persist as the firm entity already exist, flush is enough
            // $this->em->persist($updatedFirm);
            $this->em->flush($updatedFirm);
        } else {
            $formError = array_map(
                fn($error) => [
                    'message'    => $error->getMessage() . ': ' . json_encode($error->getMessageParameters()),
                ],
                iterator_to_array($firmForm->getErrors(true, true))
            );

            $errors[] = [
                'error_message' => 'Firm Form Error: ' . $formError[0]['message']
            ];
        }
        // dd($errors);

        if (!empty($errors)) {
            $data = [
                'success' => false,
                'message' => $errors[0]['error_message'],
            ];
        } else {
            $data = [
                'success' => true,
                'message' => 'Firm Updated',
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/admin/ajax/reset-firm-user-password', name: 'admin_ajax_reset_firm_user_password', methods: ['GET', 'POST'])]
    public function adminResetFirmUserPassword(Request $request, ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer): Response
    {
        $firmUserProfile = $this->em->getRepository(FirmUserProfiles::class)->find($request->request->get('firmUserProfileId'));
        $user = $firmUserProfile->getUser();

        try {
            $resetToken = $resetPasswordHelper->generateResetToken($user);

            // dd($this->generateUrl('app_reset_password', ['token' => $resetToken]));
            $email = (new TemplatedEmail())
                ->from($this->params->get('from_email'))
                ->to($user->getEmail())
                ->subject($this->params->get('app_name') . '*****' . 'RESET EMAIL REQUEST' . '*****')
                ->htmlTemplate('emails/resetPassword.html.twig')
                ->textTemplate('emails/resetPassword.txt.twig')
                ->context([
                    'user' => $user,
                    'resetUrl' => $this->generateUrl('app_reset_password', [
                        'token' => $resetToken->getToken(),
                    ], UrlGeneratorInterface::ABSOLUTE_URL),
                    'tokenLifetime' => '1 hour',
                ]);

            $mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Password reset email sent to %s', $user->getEmail()),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to send reset email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
