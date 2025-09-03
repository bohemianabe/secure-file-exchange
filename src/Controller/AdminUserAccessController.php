<?php

namespace App\Controller;

use App\Entity\Firms;
use App\Entity\States;
use App\Entity\StoragePlans;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_ADMIN")]
class AdminUserAccessController extends AbstractController
{
    use Traits\EntityActions;

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(Request $request): Response
    {
        return $this->render('admin_user_access/dashboard.html.twig', [
            'firms' => $this->_fetchWhere(Firms::class, ['active' => true]),
            'controller_name' => 'AdminUserAccessController',
            'storage_plans' => $this->_fetchAll(StoragePlans::class),
            'states' => $this->_fetchAll(States::class),
            // ag: variable to pass in needed modals for the dashboard page.
            'modals_to_include' => ['adminAddNewFirm.html.twig'],
        ]);
    }

    #[Route('/admin/admin_add_firm', name: 'admin_add_firm', methods: ['GET', 'POST'])]
    public function adminAddFirm(Request $request): Response
    {
        dd('here');
    }
}
