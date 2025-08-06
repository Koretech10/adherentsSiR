<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

abstract class Mailer {
    protected const string FROM_ADDRESS = 'no-reply@dsinreims.fr';
    protected const string FROM_NAME = 'Adhérents DS in Reims';

    public function __construct(
        protected readonly MailerInterface $mailer,
    ) {
    }

    protected function createEmailFromNoReply(): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address(self::FROM_ADDRESS, self::FROM_NAME))
        ;
    }
}