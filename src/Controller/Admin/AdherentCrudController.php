<?php

namespace App\Controller\Admin;

use App\Entity\Adherent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class AdherentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Adherent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des adhérents')
            ->setEntityLabelInSingular('Adhérent')
			->setEntityLabelInPlural('Adhérents')
            // ->setHelp('index', '')
            ->setDefaultSort(['nom' => 'ASC'])
            ->setSearchFields(['id', 'nom', 'prenom', 'date_naissance', 'date_adhesion', 'date_expiration'])
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
        $prenom = TextField::new('prenom',"Prénom")->setColumns(12);
        $pseudo = TextField::new('pseudo',"Pseudo")->setColumns(12);
        $dateNaissance = DateField::new('date_naissance',"Date de naissance")->setColumns(12);
        $dateAdhesion = DateField::new('date_adhesion',"Date d'adhésion'")->setColumns(12);
        $dateExpiration = DateField::new('date_expiration',"Date d'expiration'")->setColumns(12);

        $photo = ImageField::new('lien_image', false)->setBasePath('img/avatar/');
        $photoPath = ImageField::new('lien_image', 'Photo')->setUploadDir('public\img\avatar');

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $photo,
                $pseudo,
                $nom,
                $prenom,
                $dateNaissance,
                $dateAdhesion,
                $dateExpiration
            ];
        }
        elseif (Crud::PAGE_DETAIL === $pageName) {
            return [
                $photo,
                $pseudo,
                $nom,
                $prenom,
                $dateNaissance,
                $dateAdhesion,
                $dateExpiration
            ];
        }
        elseif (Crud::PAGE_NEW === $pageName) {
            return [
                $photoPath,
                $pseudo,
                $nom,
                $prenom,
                $dateNaissance,
                $dateAdhesion,
            ];
        }
        elseif (Crud::PAGE_EDIT === $pageName) {
            return [
                $photoPath,
                $pseudo,
                $nom,
                $prenom,
                $dateNaissance,
                $dateAdhesion,
                $dateExpiration
            ];
        }
    }
}
