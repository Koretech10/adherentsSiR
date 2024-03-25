<?php

namespace App\Controller;

use App\Entity\Adherent;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CarteController extends AbstractController
{
    private readonly ObjectManager $em;
    public function __construct(ManagerRegistry $managerRegistry)
	{
		$this->em = $managerRegistry->getManager();
	}

    #[Route('/carte', name: 'app_carte')]
    public function index(): Response
    {
        $adherents = $this->em->getRepository(Adherent::class)->getAdherentsNonExpires(date('Y-m-d'));
        return $this->render('carte/liste.html.twig', [
            'adherents' => $adherents
        ]);
    }

    #[Route(path: '/carte/model', name: 'carte_model')]
    public function modele(Request $request)
    {
        $id = $request->query->get('id_ad');
        $adherent = $this->em->getRepository(Adherent::class)->find($id);
        return $this->render('carte/carte_modele.html.twig', ['adherent' => $adherent, 'photo' => 'img/avatar/'.$adherent->getLienImage()]);
    }
}
