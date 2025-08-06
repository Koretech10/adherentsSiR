<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Service\Exporter\UserExporter;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    private const string CAN_CREATE_OR_UPDATE = 'is_granted("ROLE_USER_CREATE") or is_granted("ROLE_USER_UPDATE")';
    private const string IS_USER_MEMBER = '"ROLE_MEMBER" in object.getRoles()';
    private const string IS_USER_OR_CAN_READ = 'user === object or is_granted("ROLE_USER_READ")';
    private const string IS_USER_OR_CAN_UPDATE = 'user === object or is_granted("ROLE_USER_UPDATE")';

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des utilisateurs')
            ->setPageTitle(Crud::PAGE_DETAIL, fn (User $user) => \sprintf('Utilisateur "%s"', $user))
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un nouvel utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, fn (User $user) => \sprintf('Modifier utilisateur "%s"', $user))
            ->setEntityLabelInSingular('utilisateur')
            ->setEntityLabelInPlural('utilisateurs')
            ->setDefaultSort(['username' => 'ASC'])
            ->setSearchFields(['id', 'username'])
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

        $changePasswordAction = Action::new('changePassword', 'Changer le mot de passe')
            ->linkToCrudAction('changePassword');

        $changePasswordDetailAction = Action::new('changePasswordDetail', 'Changer le mot de passe')
            ->linkToCrudAction('changePassword')
            ->setCssClass('btn btn-primary');

        $downloadMemberCardAction = Action::new('downloadMemberCard', 'Télécharger la carte d’adhérent')
            ->linkToUrl(function () {
                /** @var User $user */
                $user = $this->getContext()?->getEntity()->getInstance();

                return $this->adminUrlGenerator
                    ->setController(MemberCrudController::class)
                    ->setAction('exportCard')
                    ->setEntityId($user->getMember()?->getId())
                    ->generateUrl();
            })
            ->setCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportToCsvAction)
            ->add(Crud::PAGE_INDEX, $changePasswordAction)
            ->add(Crud::PAGE_DETAIL, $changePasswordDetailAction)
            ->add(Crud::PAGE_DETAIL, $downloadMemberCardAction)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, 'changePassword'])
            ->reorder(Crud::PAGE_DETAIL, [Action::DELETE, Action::INDEX, 'downloadMemberCard', Action::EDIT, 'changePasswordDetail'])
            ->setPermissions([
                Action::INDEX => 'ROLE_USER_READ',
                'exportToCsv' => 'ROLE_USER_EXPORT',
                'downloadMemberCard' => new Expression(self::IS_USER_MEMBER),
                Action::DETAIL => new Expression(self::IS_USER_OR_CAN_READ),
                Action::NEW => 'ROLE_USER_CREATE',
                Action::EDIT => new Expression(self::IS_USER_OR_CAN_UPDATE),
                Action::SAVE_AND_CONTINUE => new Expression(self::CAN_CREATE_OR_UPDATE),
                'changePassword' => new Expression(self::IS_USER_OR_CAN_UPDATE),
                'changePasswordDetail' => new Expression(self::IS_USER_OR_CAN_UPDATE),
                Action::DELETE => 'ROLE_USER_DELETE',
            ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('avatar', false)
            ->setBasePath('img/avatar/')
            ->hideOnForm();
        yield TextField::new('username', 'Nom d’utilisateur');
        yield EmailField::new('email', 'E-mail')
            ->setRequired(true);
        yield TextField::new('password')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'first_options' => ['label' => 'Mot de passe'],
                'second_options' => ['label' => 'Répéter le mot de passe'],
                'required' => true,
                'mapped' => false,
            ])
            ->onlyWhenCreating();
        yield ChoiceField::new('roles', 'Rôles')
            ->allowMultipleChoices()
            ->setChoices([
                'Adhérent' => 'ROLE_MEMBER',
                'Partenaire' => 'ROLE_PARTNER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->setPermission(new Expression(self::CAN_CREATE_OR_UPDATE));
        yield AssociationField::new('member', 'Adhérent lié')
            ->setPermission('ROLE_USER_READ')
            ->onlyOnIndex();
        yield AssociationField::new('member', 'Adhérent lié')
            ->setPermission('ROLE_MEMBER')
            ->setTemplatePath('user/fields/member.html.twig')
            ->onlyOnDetail();
        yield AssociationField::new('partner', 'Partenaire lié')
            ->setPermission('ROLE_USER_READ')
            ->onlyOnIndex();
        yield AssociationField::new('partner', 'Partenaire lié')
            ->setPermission('ROLE_PARTNER')
            ->setTemplatePath('user/fields/partner.html.twig')
            ->onlyOnDetail();
        yield ImageField::new('avatar', 'Avatar')
            ->setUploadDir('public/img/avatar/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->onlyOnForms();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('username');
    }

    public function createNewFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        return $this->addPasswordEventListener($formBuilder);
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        /** @var array{ea: array{newForm: array{btn: string}}} $request
         */
        $request = $context->getRequest()->request->all();
        $submitButtonName = $request['ea']['newForm']['btn'];

        if ('saveAndReturn' === $submitButtonName && !$this->security->isGranted('ROLE_USER_UPDATE')) {
            return $this->redirect($this->adminUrlGenerator
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl()
            );
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function changePassword(AdminContext $context, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'changePassword',
            'entity' => $context->getEntity(),
            'entityFqcn' => User::class,
        ])) {
            throw new ForbiddenActionException($context);
        }

        $entity = $context->getEntity();
        if (null === $entity->getInstance() || !$entity->isAccessible()) {
            throw new \LogicException('Entity not accessible');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $entity->getInstance();
            /** @var string $plainPassword */
            $plainPassword = $form->get('password')->getData();
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));

            $this->addFlash('success', 'Mot de passe modifié avec succès.');
            $entityManager->flush();

            return $this->redirect($this->adminUrlGenerator
                ->setAction(Action::DETAIL)
                ->setEntityId($user->getId())
                ->generateUrl()
            );
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
            'referer' => $request->headers->get('referer'),
        ]);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $entityInstance;
        $avatarPath = $user->getAvatarPath();

        parent::deleteEntity($entityManager, $entityInstance);

        if (null !== $avatarPath) {
            \unlink($avatarPath);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \LogicException
     */
    public function exportToCsv(AdminContext $context, UserExporter $exporter): BinaryFileResponse
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, [
            'action' => 'exportToCsv',
            'entity' => null,
            'entityFqcn' => User::class,
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
        /** @var array<User> $users */
        $users = $queryBuilder->getQuery()->getResult();

        return $exporter->getFile($users);
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    private function hashPassword(): \Closure
    {
        return function (FormEvent $event) {
            $form = $event->getForm();

            if (!$form->isValid()) {
                return;
            }

            $password = $form->get('password')->getData();

            if (!\is_string($password)) {
                return;
            }

            /** @var User $user */
            $user = $form->getData();
            $hash = $this->userPasswordHasher->hashPassword($user, $password);

            $user->setPassword($hash);
        };
    }
}
