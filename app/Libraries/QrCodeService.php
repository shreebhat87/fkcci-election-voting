<?php

namespace App\Libraries;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /** Raw PNG bytes for the given text, ready to stream with an image/png header. */
    public function png(string $data, int $size = 300, int $margin = 8): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: $margin,
        ))->build();

        return $result->getString();
    }
}
