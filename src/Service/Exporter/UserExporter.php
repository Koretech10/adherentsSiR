<?php

declare(strict_types=1);

namespace App\Service\Exporter;

use App\Entity\User;
use App\Service\ExporterService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserExporter extends ExporterService
{
    /**
     * @param array<User> $users
     */
    public function getFile(array $users): BinaryFileResponse
    {
        return $this->getBinaryFileResponse($this->getCsvFile(
            $this->getHeaders(),
            $this->getData($users),
            'utilisateurs',
        ));
    }

    /**
     * @return array<string>
     */
    private function getHeaders(): array
    {
        return [
            'Nom d\'utilisateur',
            'Rôles',
            'Adhérent lié',
            'Partenaire lié',
        ];
    }

    /**
     * @param array<User> $users
     *
     * @return array<array<string>>
     */
    private function getData(array $users): array
    {
        $usersData = [];

        foreach ($users as $user) {
            /* @var User $user */
            $usersData[] = [
                $user->getUsername(),
                \implode(', ', $this->getRolesName($user->getRoles())),
                (string) $user->getMember(),
                (string) $user->getPartner(),
            ];
        }

        return $usersData;
    }

    /**
     * @param array<string> $roles
     *
     * @return array<string>
     */
    private function getRolesName(array $roles): array
    {
        $rolesName = [];

        foreach ($roles as $role) {
            $rolesName[] = match ($role) {
                'ROLE_USER' => 'Utilisateur',
                'ROLE_ADMIN' => 'Administrateur',
                'ROLE_MEMBER' => 'Adhérent',
                'ROLE_PARTNER' => 'Partenaire',
                default => $role,
            };
        }

        return $rolesName;
    }
}
