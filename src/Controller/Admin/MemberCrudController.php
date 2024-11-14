<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use App\Repository\MemberRepository;
use App\Service\Exporter\MemberExporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Dompdf\Dompdf;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class MemberCrudController extends AbstractCrudController
{
    private const array BUSINESS_CARD_SIZE = [0, 0, 157.91, 242.95];
    private const string IS_USER_OR_CAN_READ = 'user === object.getUser() or is_granted("ROLE_MEMBER_READ")';

    private string $projectDir;
    private string $logoPath;

    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
        KernelInterface $kernel,
    ) {
        $this->projectDir = $kernel->getProjectDir();
        $this->logoPath = \sprintf('%s/public/img/sir_logo_white.png', $this->projectDir);
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
        $exportCardAction = Action::new('exportCard', 'Télécharger la carte d’adhérent')
            ->linkToCrudAction('exportCard');
        $exportCardsAction = Action::new('exportCards', 'Télécharger les cartes d’adhérents')
            ->linkToCrudAction('exportCards')
            ->setCssClass('btn btn-info')
            ->setHtmlAttributes([
                'data-action-no-modal' => true,
            ]);

        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportToPdfAction)
            ->add(Crud::PAGE_INDEX, $exportToCsvAction)
            ->add(Crud::PAGE_INDEX, $showCardAction)
            ->add(Crud::PAGE_INDEX, $exportCardAction)
            ->addBatchAction($exportCardsAction)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'showCard', 'exportCard'])
            ->setPermissions([
                Action::INDEX => 'ROLE_MEMBER_READ',
                'exportToPdf' => 'ROLE_MEMBER_EXPORT',
                'exportToCsv' => 'ROLE_MEMBER_EXPORT',
                'showCard' => 'ROLE_MEMBER_READ',
                'exportCard' => new Expression(self::IS_USER_OR_CAN_READ),
                'exportCards' => 'ROLE_MEMBER_EXPORT',
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
        yield NumberField::new('id', 'Identifiant')
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

    public function exportToPdf(AdminContext $context): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'exportToPdf',
            'entity' => null,
            'entityFqcn' => Member::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

        $members = $this->memberRepository->getUnexpiredMembers(new \DateTime());

        $html = $this->renderView('member/export/pdf.html.twig', [
            'members' => $members,
            'logo' => $this->imageToBase64(\sprintf('%s/public/img/sir_logo_red.png', $this->projectDir)),
        ]);

        return $this->generatePdf(
            $html,
            \sprintf('Liste_adherents_%s', date('dmY_Hi')),
            'A3',
            'landscape'
        );
    }

    public function showCard(AdminContext $context): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'showCard',
            'entity' => $context->getEntity(),
            'entityFqcn' => Member::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

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

    public function exportCard(AdminContext $context): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'exportCard',
            'entity' => $context->getEntity(),
            'entityFqcn' => Member::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

        $entity = $context->getEntity();
        if (null === $entity->getInstance() || !$entity->isAccessible()) {
            throw new \LogicException('Entity not accessible');
        }

        /** @var Member $member */
        $member = $entity->getInstance();

        $html = $this->renderView('member/export/card.html.twig', [
            'logo' => $this->imageToBase64($this->logoPath),
            'member' => $member,
            'avatar' => $this->imageToBase64($this->getAvatarPath($member)),
        ]);

        return $this->generatePdf($html, 'carte', self::BUSINESS_CARD_SIZE, 'landscape');
    }

    public function exportCards(BatchActionDto $batchActionDto, AdminContext $context): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'exportCards',
            'entity' => $context->getEntity(),
            'entityFqcn' => Member::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

        $members = new ArrayCollection();
        foreach ($batchActionDto->getEntityIds() as $id) {
            $member = $this->memberRepository->findOneBy(['id' => $id]);

            if (null === $member) {
                throw new EntityNotFoundException(\sprintf('Member %s not found', $id));
            }

            $members->add([
                'entity' => $member,
                'avatar' => $this->imageToBase64($this->getAvatarPath($member)),
            ]);
        }

        $html = $this->renderView('member/export/cards.html.twig', [
            'logo' => $this->imageToBase64($this->logoPath),
            'members' => $members,
        ]);

        return $this->generatePdf($html, 'cartes', 'A4');
    }

    private function getAvatarPath(Member $member): ?string
    {
        return null === $member->getAvatar() ? null : \sprintf(
            '%s/public/img/avatar/%s',
            $this->projectDir,
            $member->getAvatar(),
        );
    }

    /**
     * @param array<float>|string $size
     */
    private function generatePdf(string $renderedHtml, string $name, array|string $size, string $orientation = 'portrait'): Response
    {
        $domPdf = new Dompdf();
        $domPdf->setPaper($size, $orientation);

        $domPdf->loadHtml($renderedHtml);
        $domPdf->render();

        return new Response($domPdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => \sprintf('attachment; filename="%s.pdf"', $name),
        ]);
    }

    private function imageToBase64(?string $path): ?string
    {
        if (null === $path) {
            return null;
        }

        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        if (false === $data) {
            throw new AssetNotFoundException(\sprintf('File "%s" not found', $path));
        }

        return \sprintf('data:image/%s;base64,%s', $type, base64_encode($data));
    }
}
