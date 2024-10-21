<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExporterService
{
    public const string FRENCH_FORMAT = 'd/m/Y';

    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        /** @var string $projectDir */
        $projectDir = $parameterBag->get('kernel.project_dir');
        $this->projectDir = $projectDir;
    }

    /**
     * @param array<string>                  $headers
     * @param array<int, array<int, string>> $data
     */
    public function getCsvFile(array $headers, array $data, string $fileName): string
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

    public function getBinaryFileResponse(string $filePath): BinaryFileResponse
    {
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'text/csv');

        return $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT)
            ->deleteFileAfterSend();
    }
}
