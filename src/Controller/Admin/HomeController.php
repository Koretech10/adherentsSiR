<?php

namespace App\Controller\Admin;

use App\Entity\Adherent;
use App\Entity\Partner;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractDashboardController
{
    private readonly ObjectManager $om;

    public function __construct(ManagerRegistry $manager)
    {
        $this->om = $manager->getManager();
    }

    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        $adherents = $this->om->getRepository(Adherent::class)->getAdherentsNonExpires(date('Y-m-d'));
        $adherentsExpires = $this->om->getRepository(Adherent::class)->getAdherentsExpires(date('Y-m-d'));

        return $this->render('index.html.twig', ['adherents' => $adherents, 'adherentsExpires' => $adherentsExpires]);
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

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Liste des partenaires', 'fas fa-list', Partner::class);
        yield MenuItem::linkToCrud('Liste des adhérents', 'fas fa-list', Adherent::class);
        yield MenuItem::linkToCrud('Liste des utilisateurs', 'fas fa-list', User::class)
            ->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToRoute('Liste des cartes', 'fas fa-pencil-alt', 'app_carte');
    }
}
