<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Repository\MemberRepository;
use App\Service\Exporter\MemberExporter;
use Doctrine\ORM\QueryBuilder;
use Dompdf\Dompdf;
use Dompdf\Options;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/member')]
class MemberCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
    ) {
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
            ->setPaginatorPageSize(60)
            ->overrideTemplate('crud/index', 'member/index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $exportToPdfAction = Action::new('exportToPdf', 'Exporter en PDF')
            ->createAsGlobalAction()
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-download')
            ->linkToCrudAction('exportToPdf');
        $exportToCsvAction = Action::new('exportToCsv', 'Exporter en CSV')
            ->linkToUrl(function () {
                $request = $this->requestStack->getCurrentRequest();

                if (null === $request) {
                    return '';
                }

                return $this->adminUrlGenerator
                    ->setAll($request->query->all())
                    ->setAction('exportToCsv')
                    ->generateUrl();
            })
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction();
        $showCardAction = Action::new('showCard', 'Afficher la carte d’adhérent')
            ->linkToCrudAction('showCard')
            ->setHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-card',
            ]);

        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportToPdfAction)
            ->add(Crud::PAGE_INDEX, $exportToCsvAction)
            ->add(Crud::PAGE_INDEX, $showCardAction)
            ->setPermissions([
                Action::INDEX => 'ROLE_MEMBER_READ',
                'exportToPdf' => 'ROLE_MEMBER_EXPORT',
                'exportToCsv' => 'ROLE_MEMBER_EXPORT',
                'showCard' => 'ROLE_MEMBER_READ',
                Action::DETAIL => 'ROLE_MEMBER_READ',
                Action::NEW => 'ROLE_MEMBER_CREATE',
                Action::EDIT => 'ROLE_MEMBER_UPDATE',
                Action::DELETE => 'ROLE_MEMBER_DELETE',
            ]);
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
        yield AssociationField::new('user', 'Utilisateur associé')
            ->autocomplete()
            ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder
                // N'affiche que les utilisateurs non liés à des adhérents ou des partenaires
                ->leftJoin('entity.partner', 'p')
                ->leftJoin('entity.member', 'm')
                ->andWhere('p.id IS NULL')
                ->andWhere('m.id IS NULL')
            )
            ->setSortProperty('username')
            ->setPermission('ROLE_MEMBER_UPDATE');
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

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \LogicException
     */
    public function exportToCsv(AdminContext $context, MemberExporter $exporter): BinaryFileResponse
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'exportToCsv',
            'entity' => null,
            'entityFqcn' => Member::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

        if (null === $context->getCrud()) {
            throw new \LogicException('Cannot get CRUD from context');
        }

        if (null === $context->getSearch()) {
            throw new \LogicException('Cannot get search from context');
        }

        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        /** @var FilterFactory $filterFactory */
        $filterFactory = $this->container->get(FilterFactory::class);
        $filters = $filterFactory->create(
            $context->getCrud()->getFiltersConfig(),
            $fields,
            $context->getEntity()
        );

        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        /** @var array<Member> $members */
        $members = $queryBuilder->getQuery()->getResult();

        return $exporter->getFile($members);
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

    public function showCard(AdminContext $context): Response
    {
        $entity = $context->getEntity();
        if (null === $entity->getInstance() || !$entity->isAccessible()) {
            throw new \LogicException('Entity not accessible');
        }
        /** @var Member $member */
        $member = $entity->getInstance();
        $avatar = null === $member->getAvatar() ? null : \sprintf('img/avatar/%s', $member->getAvatar());

        return $this->render('member/show_card.html.twig', [
            'member' => $member,
            'avatar' => $avatar,
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
