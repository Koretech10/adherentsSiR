<?php

namespace App\Form\ResetPassword;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestPasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'E-mail',
            'help' => 'Entrez votre adresse e-mail pour recevoir un lien vous permettant de réinitialiser votre mot de passe.',
            'required' => true,
        ]);

        $builder->add('submit', SubmitType::class, ['label' => 'Envoyer']);
    }
}
