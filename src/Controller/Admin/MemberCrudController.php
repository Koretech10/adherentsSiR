<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Repository\MemberRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/member')]
class MemberCrudController extends AbstractCrudController
{
    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des adhérents')
            ->setEntityLabelInSingular('adhérent')
            ->setEntityLabelInPlural('adhérents')
            ->setDefaultSort(['lastName' => 'ASC'])
            ->setSearchFields([
                'id',
                'nickname',
                'firstName',
                'lastName',
                'birthDate',
                'membershipDate',
                'expirationDate',
            ])
            ->setPaginatorPageSize(60);
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportToPdfAction = Action::new('exportToPdf', 'Exporter en PDF')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-info')
            ->linkToCrudAction('exportToPdf');

        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportToPdfAction);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('avatar', false)
            ->setBasePath('img/avatar/')
            ->hideOnForm();
        yield TextField::new('lastName', 'Nom');
        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('nickname', 'Pseudo');
        yield DateField::new('birthDate', 'Date de naissance');
        yield DateField::new('membershipDate', 'Date d’adhésion');
        yield DateField::new('expirationDate', 'Date d’expiration')
            ->hideWhenCreating();
        yield ImageField::new('avatar', 'Avatar')
            ->setUploadDir('public/img/avatar/')
            ->onlyOnForms();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('lastName')
            ->add('firstName')
            ->add('nickname')
            ->add('birthDate')
            ->add('membershipDate')
            ->add(DateTimeFilter::new('expirationDate', 'Date d’expiration'));
    }

    public function exportToPdf(): Response
    {
        $members = $this->memberRepository->getUnexpiredMembers(new \DateTime());
        /** @var string $projectDir */
        $projectDir = $this->getParameter('kernel.project_dir');

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->setPaper('A3', 'landscape');

        $html = $this->renderView('member/export/pdf.html.twig', [
            'members' => $members,
            'logo' => $this->imageToBase64(\sprintf('%s/public/img/favicon.ico', $projectDir)),
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => \sprintf('attachment; filename="Liste_adherents_%s.pdf"', date('dmY_Hi')),
        ]);
    }

    private function imageToBase64(string $path): string
    {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        if (false === $data) {
            throw new AssetNotFoundException(\sprintf('File "%s" not found', $path));
        }

        return \sprintf('data:image/%s;base64,%s', $type, base64_encode($data));
    }
}
