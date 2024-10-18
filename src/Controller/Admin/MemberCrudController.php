<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MemberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des adhérents')
            ->setEntityLabelInSingular('Adhérent')
            ->setEntityLabelInPlural('Adhérents')
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
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ImageField::new('avatar', false)
                ->setBasePath('img/avatar/')
                ->hideOnForm(),
            TextField::new('lastName', 'Nom'),
            TextField::new('firstName', 'Prénom'),
            TextField::new('nickname', 'Pseudo'),
            DateField::new('birthDate', 'Date de naissance'),
            DateField::new('membershipDate', 'Date d’adhésion'),
            DateField::new('expirationDate', 'Date d’expiration')
                ->hideWhenCreating(),
            ImageField::new('avatar', 'Avatar')
                ->setUploadDir('public/img/avatar/')
                ->onlyOnForms(),
        ];
    }
}
