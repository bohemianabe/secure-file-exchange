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

    #[Route('/admin/ajax/new-firm-submit', name: 'admin_new_firm_submit', methods: ['GET', 'POST'])]
    public function adminNewFormSubmit(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        // dd($request->request);

        // ag: empty error array to collect errors while processing new firm and users
        $errors = array();
        // ag: process the new firm
        $newFirm = new Firms();

        $firmForm = $this->createForm(FirmType::class, $newFirm);
        $firmForm->handleRequest($request);

        if ($firmForm->isSubmitted() && $firmForm->isValid()) {
            $logoFile = $firmForm->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('firm_logo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
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

            // $newFirm = true;

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

                // $newUser = true;

                if ($newUser) {
                    // $user = $this->em->getRepository(User::class)->find(2);
                    // $firm = $this->em->getRepository(Firms::class)->find(1);
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
}
