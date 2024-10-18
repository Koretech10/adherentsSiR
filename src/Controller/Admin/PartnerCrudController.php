<?php

namespace App\Controller\Admin;

use App\Entity\Partner;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PartnerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Partner::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des partenaires')
            ->setEntityLabelInSingular('Partenaire')
            ->setEntityLabelInPlural('Partenaires')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['id', 'name', 'address', 'postalCode', 'city', 'offer'])
            ->setPaginatorPageSize(60);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('address', 'Adresse'),
            TextField::new('postalCode', 'Code postal'),
            TextField::new('city', 'Ville'),
            TextareaField::new('offer', 'Avantages'),
        ];
    }
}
