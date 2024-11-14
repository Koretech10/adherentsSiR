<?php

declare(strict_types=1);

namespace App\Service\Exporter;

use App\Entity\Member;
use App\Service\ExporterService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberExporter extends ExporterService
{
    /**
     * @param array<Member> $members
     */
    public function getFile(array $members): BinaryFileResponse
    {
        return $this->getBinaryFileResponse($this->getCsvFile(
            $this->getHeaders(),
            $this->getData($members),
            'adhérents',
        ));
    }

    /**
     * @return array<string>
     */
    private function getHeaders(): array
    {
        return [
            'Identifiant',
            'Nom',
            'Prénom',
            'Pseudo',
            'Date de naissance',
            'Date d\'adhésion',
            'Date d\'expiration',
            'Utilisateur lié',
        ];
    }

    /**
     * @param array<Member> $members
     *
     * @return array<int, array<int, string>>
     */
    private function getData(array $members): array
    {
        $membersData = [];

        foreach ($members as $member) {
            /* @var Member $member */
            $membersData[] = [
                $member->getId(),
                $member->getLastName(),
                $member->getFirstName(),
                $member->getNickname(),
                $member->getBirthDate()->format(self::FRENCH_FORMAT),
                $member->getMembershipDate()->format(self::FRENCH_FORMAT),
                $member->getExpirationDate()->format(self::FRENCH_FORMAT),
                (string) $member->getUser(),
            ];
        }

        return $membersData;
    }
}