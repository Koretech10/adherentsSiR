<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use App\Service\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class ResetPasswordMailer extends Mailer
{
    public function sendPasswordResetMail(string $to,ResetPasswordToken $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_ADDRESS, self::FROM_NAME))
            ->to($to)
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $this->mailer->send($email);
    }
}