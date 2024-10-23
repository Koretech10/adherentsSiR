<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\MemberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractDashboardController
{
    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        $now = new \DateTime();

        return $this->render('index.html.twig', [
            'unexpiredMembers' => $this->memberRepository->getUnexpiredMembers($now),
            'expiredMembers' => $this->memberRepository->getExpiredMembers($now),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img 
                class="img-responsive d-flex mx-auto" 
                height="150px" 
                alt="Adhérents Switch In Reims" 
                src="img/sir_logo_red.png" 
            />')
            ->setFaviconPath('img/favicon.ico');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd/MM/yyyy')
            ->setDateTimeFormat('dd/MM/yyyy HH:mm:ss')
            ->setTimeFormat('HH:mm');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $avatarPath = $user instanceof User ? $user->getAvatarPath() : null;

        return parent::configureUserMenu($user)
            ->setAvatarUrl($avatarPath);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section();
        yield MenuItem::linkToCrud('Liste des adhérents', 'fa-solid fa-people-group', Member::class);
        yield MenuItem::linkToRoute('Liste des cartes', 'fa-solid fa-id-card', 'member_card_list');

        yield MenuItem::section();
        yield MenuItem::linkToCrud('Liste des partenaires', 'fa-solid fa-shop', Partner::class);

        yield MenuItem::section();
        yield MenuItem::linkToCrud('Liste des utilisateurs', 'fa-solid fa-user-gear', User::class)
            ->setPermission('ROLE_ADMIN');
    }
}
