<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use App\Service\Mailer;

class UserMailer extends Mailer
{
    public function sendUserCreatedMail(string $to, string $userName): void
    {
        $email = $this->createEmailFromNoReply()
            ->to($to)
            ->subject('Bienvenue sur Adhérents DS in Reims')
            ->htmlTemplate('user/email/created.html.twig')
            ->context([
                'userName' => $userName,
            ])
        ;

        $this->mailer->send($email);
    }
}