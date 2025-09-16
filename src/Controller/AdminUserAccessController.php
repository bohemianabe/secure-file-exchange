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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted("ROLE_ADMIN")]
class AdminUserAccessController extends AbstractController
{
    use Traits\EntityActions;

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(Request $request): Response
    {
        // dd($this->_fetchWhere(Firms::class, ['active' => true]));
        return $this->render('admin_user_access/dashboard.html.twig', [
            'firms' => $this->_fetchWhere(Firms::class, ['active' => true]),
            'storage_plans' => $this->_fetchAll(StoragePlans::class),
            'states' => $this->_fetchAll(States::class),
            'firm_user_types' => $this->firmUserTypes,
            // ag: variable to pass in needed modals for the dashboard page.
            'modals_to_include' => ['adminAddNewFirm.html.twig'],
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

    #[Route('/admin/firm_user/{firmUserProfileId}/modal', name: "admin_firm_user_modal", methods: ['GET', 'POST'])]
    #[Route('/admin/firm_user/modal/submit', name: "admin_firm_user_modal_submit")]
    public function adminFirmUserProfileModal(Request $request, $firmUserProfileId, EntityManagerInterface $em): Response
    {
        // $firmUserProfileId = $request->request->get('firmUserProfileId');

        $firmUserProfile = $em->getRepository(FirmUserProfiles::class)->find($firmUserProfileId);

        return $this->render('layouts/modals/adminEditFirmUserProfile.html.twig', [
            'firmUserProfile' => $firmUserProfile,
        ]);
    }

    #[Route('/admin/deactivate_firm/{firm}', name: 'admin_toggle_firm_status')]
    public function togglerFirmStatus(Request $request, ?Firms $firm, EntityManagerInterface $em)
    {
        if ($firm->getActive()) {
            $firm->setActive(false);
        } else {
            $firm->setActive(true);
        }

        $flushFirm = $em->flush($firm);

        return $this->redirect($request->headers->get('referer'));
    }

    // ag: ********************************************** ajax calls below here **********************************
    #[Route('/admin/ajax/new-firm-submit', name: 'admin_new_firm_submit', methods: ['GET', 'POST'])]
    public function adminNewFormSubmit(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {

        // ag: empty error array to collect errors while processing new firm and users
        $errors = array();
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
                    dd($logoFile);
                } catch (FileException $e) {
                    dd($e->getMessage());
                    // Handle exception / log
                    $errors[] = [
                        'error_type' => 'Firm logo upload error.',
                        'error_message' => $e->getMessage(),
                    ];
                }

                $newFirm->setLogo($newFilename);
            }

            $em->persist($newFirm);
            $em->flush($newFirm);

            // ag: if firm was created then go on to add the firmUserProfile
            if ($newFirm) {
                // ag: created the new user
                $newUser = new User();
                $newUserForm = $this->createForm(UserType::class, $newUser);
                $newUserForm->handleRequest($request);

                if ($newUserForm->isSubmitted() && $newUserForm->isValid()) {
                    $em->persist($newUser);
                    $em->flush($newUser);
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

                    // dd($firmUserProfileForm->getErrors(true, true));

                    if ($firmUserProfileForm->isSubmitted() && $firmUserProfileForm->isValid()) {
                        $em->persist($newFirmUserProfile);
                        $em->flush($newFirmUserProfile);
                    } else {
                        $errors[] = [
                            'error_type' => 'New Firm User Profile Form Error',
                            'error_message' => iterator_to_array($firmUserProfileForm->getErrors(true, true)),
                        ];
                    }
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
                'message' => 'New Firm and Primary User Successfully Added',
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
    public function updateFirmData(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // dd($request->request);
        $errors = array();
        // ag: process the new firm
        $firmId = $request->request->get('firmId') ?? null;

        if ($firmId) {
            $updatedFirm = $em->getRepository(Firms::class)->find($firmId);
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
            // $em->persist($updatedFirm);
            $em->flush($updatedFirm);
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
}
