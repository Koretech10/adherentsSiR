<?php

namespace App\Controller\Admin;

use App\Entity\Partner;
use App\Service\Exporter\PartnerExporter;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class PartnerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Partner::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des partenaires')
            ->setEntityLabelInSingular('partenaire')
            ->setEntityLabelInPlural('partenaires')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['id', 'name', 'address', 'postalCode', 'city', 'offer'])
            ->setPaginatorPageSize(60);
    }

    public function configureActions(Actions $actions): Actions
    {
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

        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportToCsvAction)
            ->setPermissions([
                Action::INDEX => 'ROLE_PARTNER_READ',
                'exportToCsv' => 'ROLE_PARTNER_EXPORT',
                Action::DETAIL => 'ROLE_PARTNER_READ',
                Action::NEW => 'ROLE_PARTNER_CREATE',
                Action::EDIT => 'ROLE_PARTNER_UPDATE',
                Action::DELETE => 'ROLE_PARTNER_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');
        yield TextField::new('address', 'Adresse');
        yield TextField::new('postalCode', 'Code postal');
        yield TextField::new('city', 'Ville');
        yield TextareaField::new('offer', 'Avantages');
        yield AssociationField::new('user', 'Utilisateur associé')
            ->autocomplete()
            ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder
                // N'affiche que les utilisateurs non liés à des adhérents ou des partenaires
                ->leftJoin('entity.partner', 'p')
                ->leftJoin('entity.member', 'm')
                ->andWhere('p.id IS NULL')
                ->andWhere('m.id IS NULL')
            )
            ->setSortProperty('username');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('address')
            ->add('postalCode')
            ->add('city')
            ->add('offer');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function exportToCsv(AdminContext $context, PartnerExporter $exporter): BinaryFileResponse
    {
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
        /** @var array<Partner> $partners */
        $partners = $queryBuilder->getQuery()->getResult();

        return $exporter->getFile($partners);
    }
}
