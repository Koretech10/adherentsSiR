<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'root:manage',
    description: "Changer le mot de passe du compte root. Le créer s'il n'existe pas.",
)]
class RootManageCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Conditions à respecter pour le mot de passe
        $output->writeln([
            "Vous êtes sur le point d'initialiser ou de modifier le mot de passe du compte root de l'application.",
            'Ce mot de passe doit répondre aux exigences de sécurité suivantes :',
            '    - 16 caractères minimum',
            '    - Au moins une lettre minuscule',
            '    - Au moins une lettre MAJUSCULE',
            '    - Au moins un chiffre',
            '    - Au moins un de ces caractères spéciaux : ! @ # $ % ^ & *',
        ]);

        // Pose la question
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $passwordQuestion = new Question('<question>Entrez le nouveau mot de passe root :</question> ');

        // Configure la confidentialité, la validation et l'infinité de la question
        $passwordQuestion
            ->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function (?string $value): string {
                if (null !== $value
                    && 1 === preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])(?=.{16,})/', $value)
                ) {
                    return $value;
                }

                throw new \RuntimeException('Le mot de passe ne répond pas aux exigences de sécurité.');
            })
            ->setMaxAttempts(null)
        ;

        // Enregistrer la réponse
        /** @var string $password */
        $password = $helper->ask($input, $output, $passwordQuestion);

        $root = $this->userRepository->findOneBy(['username' => 'root']);
        if (null === $root) { // Créer l'utilisateur root car il n'existe pas
            $output->writeln("<comment>Le compte root n'existe pas. Il va être créé.</comment>");
            $root = new User();
            $root
                ->setUsername('root')
                ->setPassword($this->passwordHasher->hashPassword($root, $password))
                ->setRoles([
                    'ROLE_USER',
                    'ROLE_ADMIN',
                ])
            ;
            $this->em->persist($root);

            $output->writeln('<info>Compte root créé avec succès.</info>');
        } else { // Mettre à jour le mot de passe root
            $root->setPassword($this->passwordHasher->hashPassword($root, $password));

            $output->writeln('<info>Mot de passe root modifié avec succès.</info>');
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
