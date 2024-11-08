<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\MemberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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
            ->setTitle('<img class="d-flex mx-auto menu-logo" src="img/sir_logo_red.png" alt="Adhérents Switch In Reims" />')
            ->setFaviconPath('favicon.ico');
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
        /** @var User $user */
        $avatarPath = $user->getAvatarPath();

        return parent::configureUserMenu($user)
            ->setAvatarUrl($avatarPath)
            ->addMenuItems([
                MenuItem::linkToCrud('Mon profil', 'fa fa-user', User::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($user->getId()),
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Liste des adhérents', 'fa-solid fa-people-group', Member::class)
            ->setPermission('ROLE_MEMBER_READ');
        yield MenuItem::linkToCrud('Liste des partenaires', 'fa-solid fa-shop', Partner::class)
            ->setPermission('ROLE_PARTNER_READ');
        yield MenuItem::linkToCrud('Liste des utilisateurs', 'fa-solid fa-user-gear', User::class)
            ->setPermission('ROLE_ADMIN');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addAssetMapperEntry('logo');
    }
}
