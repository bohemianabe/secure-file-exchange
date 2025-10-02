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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PropertyAccess\PropertyAccess;
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
            'modals_to_include' => ['adminAddNewFirm.html.twig', 'adminImportFirm.html.twig'],
            'chartData' => ['activeFirmMetrics' => $this->_getActiveFirmMetrics(), 'storagePlanMetrics' => $this->_getStoragePlanMetrics()],
        ]);
    }

    #[Route('/admin/firm-view/{firm}', name: 'admin_firm_view')]
    public function adminFirmViewPage(Request $request, Firms $firm): Response
    {
        // dd($firm->getFirmUserProfiles());
        return $this->render('admin_user_access/firmView.html.twig', [
            'firm' => $firm,
            'storage_plans' => $this->_fetchAll(StoragePlans::class),
            'states' => $this->_fetchAll(States::class),
            'modals_to_include' => ['adminToggleFirmStatus.html.twig', 'adminAddNewFirmUserProfile.html.twig', 'defaultTemplate.html.twig']
        ]);
    }

    #[Route('/admin/firm-user/new/{firmId}/modal', name: 'admin_new_firm_user_modal')]
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

    #[Route('/admin/deactivate-firm/{firm}', name: 'admin_toggle_firm_status')]
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
    #[Route('/admin/download-firm-import-csv', name: 'admin_download_firm_import_template_csv')]
    #[Route('/admin/firm-import-csv/submit', name: 'admin_firm_import_submit', methods: ['POST'])]
    public function adminImportFirmDownload(Request $request, ManagerRegistry $registry)
    {
        // ag: arrays for the csv template
        $csvHeader = ['firm_name', 'firm_account', 'firm_storage_plan', 'firm_addr1', 'firm_addr2', 'firm_city', 'firm_state', 'firm_zip', 'firm_phone', 'firm_active', 'user_email', 'profile_first_name', 'profile_last_name', 'profile_title', 'profile_phone', 'profile_bulk_action', 'profile_see_all_files', 'profile_contact_user'];

        // Example row of demo data
        $exampleRow = ['Test Firm', 'testfirm123', 'professional', '1800 Pennsylvania Ave', '', 'Alexandria', 'VA', '22202', '333-999-6666', 'true', 'johndoe@example.com', 'John', 'Doe', 'CEO', '123-456-7890', 'true', 'true', 'true'];

        if ($request->get('_route') == 'admin_firm_import_submit') {
            $file = $request->files->get('file');

            // ag: if no file is submitted return this error
            if (is_null($file)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No File Selected',
                ]);
            }
            $path = $file->getRealPath();

            if (($handle = fopen($path, 'r')) !== false) {
                $header = fgetcsv($handle); // first row
                $rows = [];
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = array_combine($header, $data);
                }
                fclose($handle);
            }

            // ag: safety check makes sure CSV header row matches the expected fields
            $headerArrayCheck = array_diff($header, $csvHeader);

            if (!empty($headerArrayCheck)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'CSV headers didn\'t match expected fields. Please use the download-able template.',
                ]);
            } else {
                // ag: headers are good proceed to safety value checks
                $errors = [];

                // ag: get names of plans. Use array_map since entityManger returns an object
                $storagePlans = array_map(
                    fn($plan) => strtolower($plan->getName()),
                    $this->em->getRepository(StoragePlans::class)->findAll()
                );
                // dd($storagePlans);

                $states = array_map(
                    fn($state) => strtoupper($state->getCode()),
                    $this->em->getRepository(States::class)->findAll()
                );

                $trueFalseColumns = ['firm_active', 'profile_bulk_action', 'profile_see_all_files', 'profile_contact_user'];

                // ag: parse through the CSV and make the final array to import if no errors are found.
                $transformed = array_map(function ($row) use (&$errors, $storagePlans, $states, $trueFalseColumns) {
                    $firm = [];
                    $user = [];
                    $profile = [];

                    foreach ($row as $key => $value) {
                        // normalize blank values
                        $value = trim((string)$value);

                        if (str_starts_with($key, 'firm_')) {
                            $cleanKey = str_replace('firm_', '', $key);

                            // === FIRM ACCOUNT CHECK ===
                            if ($cleanKey === 'account') {
                                $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $value));
                                if ($normalized !== $value) {
                                    $errors[] = [
                                        'message' => 'firm_account: ' . $value . '/ must be a lowercase one word.'
                                    ];
                                }
                                $value = $normalized;
                            }

                            // === FIRM NAME CHECK ===
                            if ($cleanKey === 'name' && $value === '') {
                                $errors[] = [
                                    'message' => 'firm_name: ' . $value . '/ Firm name cannot be empty.',
                                ];
                            }

                            // === STORAGE PLAN CHECK ===
                            if ($cleanKey === 'storage_plan') {
                                if (!in_array(strtolower($value), $storagePlans)) {
                                    $errors[] = [
                                        'message' => 'firm_storage_plan: ' . $value . '/ Storage plan does not exist in storage_plans table.',
                                    ];
                                }
                            }

                            // === STATE CHECK ===
                            if ($cleanKey === 'state') {
                                $value = strtoupper($value);
                                if (!in_array($value, $states)) {
                                    $errors[] = [
                                        'message' => 'firm_state: ' . $value . '/ Firm state must be a valid 2-letter code from states table.',
                                    ];
                                }
                            }

                            // === TRUE/FALSE columns ===
                            if (in_array($key, $trueFalseColumns)) {
                                $value = strtolower($value) === 'true' ? true : false;
                            }

                            $firm[$cleanKey] = $value;
                        } elseif (str_starts_with($key, 'user_')) {
                            $cleanKey = str_replace('user_', '', $key);

                            // === USER EMAIL CHECK ===
                            if ($cleanKey === 'email') {
                                if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    $errors[] = [
                                        'message' => 'user_email: ' . $value . '/ User email cannot be empty and must be valid.',
                                    ];
                                }
                            }

                            $user[$cleanKey] = $value;
                        } elseif (str_starts_with($key, 'profile_')) {
                            $cleanKey = str_replace('profile_', '', $key);

                            // === TRUE/FALSE columns in profile ===
                            if (in_array($key, $trueFalseColumns)) {
                                $value = strtolower($value) === 'true' ? true : false;
                            }

                            $profile[$cleanKey] = $value;
                        }
                    }

                    return [
                        'firm'              => $firm,
                        'user'              => $user,
                        'firm_user_profile' => $profile,
                    ];
                }, $rows);

                // dd($errors[0]['message']);
                // ag: if there are found errors return them with a warning
                if (!empty($errors)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $errors[0]['message'] . ' Please review your file and resubmit.',
                    ]);
                } else {
                    // ag: after passing the value checks and the data is good now process the data to the db
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $now = new \DateTime();

                    $batchSize = 20;
                    $i = 0;

                    foreach ($transformed as $row) {
                        try {
                            // ag: establish the entities
                            $firm = new Firms();
                            $user = new User();
                            $profile = new FirmUserProfiles();

                            // Hydrate Firm
                            foreach ($row['firm'] as $field => $value) {

                                try {
                                    if ($field === 'storage_plan') {
                                        // Lookup the StoragePlans entity by its "name"
                                        $storagePlan = $this->em->getRepository(StoragePlans::class)
                                            ->findOneBy(['name' => $value]);

                                        // Assign the object, not the string
                                        $firm->setStoragePlan($storagePlan);
                                    } else {
                                        $accessor->setValue($firm, $field, $value);
                                    }
                                } catch (\Throwable $e) {
                                    $errors[] = [
                                        'message' => $e->getMessage(),
                                    ];
                                }
                            }

                            // Hydrate User
                            foreach ($row['user'] as $field => $value) {
                                $accessor->setValue($user, $field, $value);
                            }

                            // // Hydrate FirmUserProfile
                            foreach ($row['firm_user_profile'] as $field => $value) {
                                $accessor->setValue($profile, $field, $value);
                            }

                            $firm->setCreatedDate($now);
                            $firm->setUpdatedDate($now);

                            $user->setIsActive(true);
                            $user->setRoles(['ROLE_FIRM']);
                            $user->setCreatedDate($now);
                            $user->setUpdatedDate($now);

                            // Setup relations
                            $profile->setCreatedDate($now);
                            $profile->setUpdatedDate($now);
                            $profile->setFirm($firm);
                            $profile->setUser($user);

                            // Persist everything
                            $this->em->persist($firm);
                            $this->em->persist($user);
                            $this->em->persist($profile);

                            if (($i % $batchSize) === 0) {
                                $this->em->flush();
                                $this->em->clear(); // detach all entities to free memory

                            }
                            $i++;
                        } catch (\Throwable $e) {
                            $errors[] = [
                                'message' => $e->getMessage(),
                            ];

                            // ag: incase the try fails and it closes the entityManger interface reset it here to get a fresh entitymangerinterface connection
                            if (!$this->em->isOpen()) {
                                $this->em = $registry->resetManager();
                            }
                        }
                    }

                    // final flush
                    try {
                        $this->em->flush();
                        $this->em->clear();
                    } catch (\Throwable $e) {
                        $errors[] = ['message' => $e->getMessage()];

                        // ag: ensure I always have a working EntityManager incase one of the rows above fails and closes the entityMangerinterface
                        if (
                            !$this->em instanceof EntityManagerInterface ||
                            !$this->em->isOpen()
                        ) {
                            // resetManager() will clear and rebuild the EntityManager service to a fresh one if it ends up crashing during a flush() up above.
                            $this->em = $registry->resetManager();
                        }
                    }

                    if (empty($errors)) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'CSV successfully added.',
                        ]);
                    } else {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'There was an issue with row(s) in your CSV. Please review your file. ' . $errors[0]['message'],
                        ]);
                    }
                }
            }
        }

        // Convert arrays into CSV lines
        $headerLine = implode(',', $csvHeader);
        $dataLine   = implode(',', array_map(function ($value) {
            // Wrap in quotes if value contains a comma or space
            return (strpos($value, ',') !== false || strpos($value, ' ') !== false)
                ? '"' . $value . '"'
                : $value;
        }, $exampleRow));

        $csvContent = $headerLine . "\n" . $dataLine;

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="firm-template.csv"',
            ]
        );
    }

    #[Route('/admin/ajax/new-firm-submit', name: 'admin_new_firm_submit', methods: ['POST'])]
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
                        } catch (\Throwable $e) {
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

    #[Route('/admin/ajax/{firmId}/add-new-firm-user', name: 'admin_add_new_firm_user', methods: ['POST'])]
    public function adminAddNewFirmUser(Request $request, $firmId, MailerInterface $mailer)
    {
        $firm = $this->em->getRepository(Firms::class)->find($firmId);
        $now = new \DateTime();

        $newUser = new User();
        $newUserForm = $this->createForm(UserType::class, $newUser);
        $newUserForm->handleRequest($request);

        // dd($request->request);
        $errors = array();
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
            $newFirmUserProfile->setFirm($firm);

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
                } catch (\Throwable $e) {
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
        } else {
            $errors[] = [
                'error_type' => 'New User Profile Form Error',
                'error_message' => 'Unable to create new user. Contact an administrator.',
            ];
        }

        if (empty($errors)) {
            return new JsonResponse([
                'success' => true,
                'message' => 'New Firm User created.',
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => $errors[0]['error_type'] . ' - Contact an administrator.',
            ]);
        }
    }
}
