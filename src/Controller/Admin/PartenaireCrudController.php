<?php

namespace App\Controller\Admin;

use App\Entity\Partenaire;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PartenaireCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Partenaire::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des partenaires')
            ->setEntityLabelInSingular('Partenaire')
			->setEntityLabelInPlural('Partenaires')
            // ->setHelp('index', '')
            ->setDefaultSort(['nom' => 'ASC'])
            ->setSearchFields(['id', 'nom', 'adresse', 'cp', 'vile', 'avantages'])
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
        $nom = TextField::new('nom',"Nom")->setColumns(12);
        $adresse = TextField::new('adresse',"Adresse")->setColumns(12);
        $cp = TextField::new('cp',"Code Postal")->setColumns(12);
        $ville = TextField::new('ville',"Ville")->setColumns(12);
        $avantages = TextField::new('avantages',"Avantages'")->setColumns(12);

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $nom,
                $adresse,
                $cp,
                $ville,
                $avantages
            ];
        }
        elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $nom,
                $adresse,
                $cp,
                $ville,
                $avantages
            ];
        }
        elseif (Crud::PAGE_NEW === $pageName) {
            return [
                $nom,
                $adresse,
                $cp,
                $ville,
                $avantages
            ];
        }
        elseif (Crud::PAGE_EDIT === $pageName) {
            return [
                $nom,
                $adresse,
                $cp,
                $ville,
                $avantages
            ];
        }
    }
}
