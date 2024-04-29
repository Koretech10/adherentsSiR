<?php

namespace App\Controller;

use App\Entity\Adherent;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Dompdf\Dompdf;
use Dompdf\Options;

class CarteController extends AbstractController
{
    private readonly ObjectManager $em;
    public function __construct(ManagerRegistry $managerRegistry)
	{
		$this->em = $managerRegistry->getManager();
	}

    #[Route('/carte', name: 'app_carte')]
    public function index(Request $request): Response
    {
        $adherents = $this->em->getRepository(Adherent::class)->getAdherentsNonExpires(date('Y-m-d'));

        if($request->request->get('pdf') != 1){
            return $this->render('carte/liste.html.twig', [
                'adherents' => $adherents
            ]);
        }else{
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Helvetica');
            $dompdf = new Dompdf($pdfOptions);
            $html = $this->renderView('carte/liste_pdf.html.twig', [
                'adherents' => $adherents,
                'logo' => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/img/favicon.ico')
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A3', 'landscape');
            $dompdf->render();
            $output = $dompdf->output();

            return new Response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Liste_adherents_'.date('dmY_Hi').'.pdf"'
            ]);
        }
    }

    #[Route(path: '/carte/model', name: 'carte_model')]
    public function modele(Request $request)
    {
        $id = $request->query->get('id_ad');
        $adherent = $this->em->getRepository(Adherent::class)->find($id);
        return $this->render('carte/carte_modele.html.twig', ['adherent' => $adherent, 'photo' => 'img/avatar/'.$adherent->getLienImage()]);
    }

    private function imageToBase64($path) {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}
