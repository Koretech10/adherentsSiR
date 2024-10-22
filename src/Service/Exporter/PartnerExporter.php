<?php

declare(strict_types=1);

namespace App\Service\Exporter;

use App\Entity\Partner;
use App\Service\ExporterService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartnerExporter extends ExporterService
{
    /**
     * @param array<Partner> $partners
     */
    public function getFile(array $partners): BinaryFileResponse
    {
        return $this->getBinaryFileResponse($this->getCsvFile(
            $this->getHeaders(),
            $this->getData($partners),
            'partenaires',
        ));
    }

    /**
     * @return array<string>
     */
    private function getHeaders(): array
    {
        return [
            'Nom',
            'Adresse',
            'Code postal',
            'Ville',
            'Avantages',
        ];
    }

    /**
     * @param array<Partner> $partners
     *
     * @return array<int, array<int, string>>
     */
    private function getData(array $partners): array
    {
        $partnersData = [];

        foreach ($partners as $partner) {
            /* @var Partner $partner */
            $partnersData[] = [
                $partner->getName(),
                $partner->getAddress(),
                $partner->getPostalCode(),
                $partner->getCity(),
                $partner->getOffer(),
            ];
        }

        return $partnersData;
    }
}
