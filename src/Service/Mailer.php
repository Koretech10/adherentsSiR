<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;

abstract class Mailer {
    protected const string FROM_ADDRESS = 'no-reply@dsinreims.fr';
    protected const string FROM_NAME = 'Adhérents DS in Reims';

    public function __construct(
        protected readonly MailerInterface $mailer,
    ) {
    }
}