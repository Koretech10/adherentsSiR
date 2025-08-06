<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Mailer\UserMailer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class UserCreatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserMailer $userMailer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'onUserCreated',
        ];
    }

    public function onUserCreated(AfterEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof User) {
            return;
        }

        /** @var string $email */
        $email = $entity->getEmail();

        $this->userMailer->sendUserCreatedMail($email, $entity->getUsername());
    }
}