<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Member;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

readonly class ExporterService
{
    private const string FRENCH_FORMAT = 'd/m/Y';

    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        /** @var string $projectDir */
        $projectDir = $parameterBag->get('kernel.project_dir');
        $this->projectDir = $projectDir;
    }

    /* METHODES GENERATIVES */

    /**
     * @param array<Member> $members
     */
    public function getMembersExportFile(array $members): BinaryFileResponse
    {
        return $this->getBinaryFileResponse($this->getCsvFile(
            $this->getMembersHeaders(),
            $this->getMembersData($members),
            'adhérents',
        ));
    }

    /* METHODES STRUCTURANTES */

    /**
     * @return array<string>
     */
    private function getMembersHeaders(): array
    {
        return [
            'Nom',
            'Prénom',
            'Pseudo',
            'Date de naissance',
            'Date d\'adhésion',
            'Date d\'expiration',
        ];
    }

    /**
     * @param array<Member> $members
     *
     * @return array<int, array<int, string>>
     */
    private function getMembersData(array $members): array
    {
        $membersData = [];

        foreach ($members as $member) {
            /* @var Member $member */
            $membersData[] = [
                $member->getLastName(),
                $member->getFirstName(),
                $member->getNickname(),
                $member->getBirthDate()->format(self::FRENCH_FORMAT),
                $member->getMembershipDate()->format(self::FRENCH_FORMAT),
                $member->getExpirationDate()->format(self::FRENCH_FORMAT),
            ];
        }

        return $membersData;
    }

    /* METHODES GENERIQUES */

    /**
     * @param array<string>                  $headers
     * @param array<int, array<int, string>> $data
     */
    private function getCsvFile(array $headers, array $data, string $fileName): string
    {
        $now = (new \DateTime())->format('Ymd');
        $filePath = \sprintf('%s/public/fics/export_%s_%s.csv', $this->projectDir, $fileName, $now);

        $file = \fopen($filePath, 'wb');
        if (false === $file) {
            throw new IOException(\sprintf('Cannot open file « %s »', $filePath));
        }

        \fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF)); // Encodage BOM pour compatibilité avec Excel
        \fputcsv($file, $headers, ';');

        foreach ($data as $row) {
            \fputcsv($file, $row, ';');
        }

        \fclose($file);

        return $filePath;
    }

    private function getBinaryFileResponse(string $filePath): BinaryFileResponse
    {
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'text/csv');

        return $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT)
            ->deleteFileAfterSend();
    }
}
