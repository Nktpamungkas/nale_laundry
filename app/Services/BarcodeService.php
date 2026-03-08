<?php

namespace App\Services;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class BarcodeService
{
    public function qrPngDataUri(string $value, int $size = 240, int $margin = 8): string
    {
        $qrCode = new QrCode(
            data: $value,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $result = (new PngWriter())->write($qrCode);

        return $result->getDataUri();
    }
}
