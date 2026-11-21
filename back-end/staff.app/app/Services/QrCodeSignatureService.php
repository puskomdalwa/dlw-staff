<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;

class QrCodeSignatureService
{
    /**
     * Render a styled QR code data URI (circular slate dots, UII Dalwa navy finders,
     * and cropped UII Dalwa logo in a rounded white badge at center).
     */
    public static function generateStyledVerifyQr(string $verifyUrl): string
    {
        try {
            $logoPath = base_path('../public_html/img/logo_uii.png');

            if (extension_loaded('gd') && file_exists($logoPath)) {
                $navy = [15, 51, 108];      // #0F336C - matches UII Dalwa logo
                $dark = [17, 24, 39];       // #111827 - high contrast slate black
                $whiteRgb = [255, 255, 255];

                $options = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'eccLevel' => EccLevel::H,
                    'scale' => 10,
                    'addQuietzone' => true,
                    'quietzoneSize' => 2,
                    'imageTransparent' => false,
                    'drawCircularModules' => true,
                    'circleRadius' => 0.43,
                    'keepAsSquare' => [
                        QRMatrix::M_FINDER_DARK,
                        QRMatrix::M_FINDER_DOT,
                        QRMatrix::M_ALIGNMENT_DARK,
                    ],
                    'gdImageUseUpscale' => true,
                    'addLogoSpace' => true,
                    'logoSpaceWidth' => 9,
                    'logoSpaceHeight' => 9,
                    'moduleValues' => [
                        QRMatrix::M_FINDER_DARK => $navy,
                        QRMatrix::M_FINDER_DOT => $navy,
                        QRMatrix::M_ALIGNMENT_DARK => $navy,
                        QRMatrix::M_DATA_DARK => $dark,
                        QRMatrix::M_DARKMODULE => $dark,
                        QRMatrix::M_TIMING_DARK => $dark,
                        QRMatrix::M_FORMAT_DARK => $dark,
                        QRMatrix::M_VERSION_DARK => $dark,
                        QRMatrix::M_QUIETZONE => $whiteRgb,
                        QRMatrix::M_NULL => $whiteRgb,
                        QRMatrix::M_DATA => $whiteRgb,
                        QRMatrix::M_FINDER => $whiteRgb,
                        QRMatrix::M_SEPARATOR => $whiteRgb,
                        QRMatrix::M_ALIGNMENT => $whiteRgb,
                        QRMatrix::M_TIMING => $whiteRgb,
                        QRMatrix::M_FORMAT => $whiteRgb,
                        QRMatrix::M_VERSION => $whiteRgb,
                        QRMatrix::M_LOGO => $whiteRgb,
                    ],
                    'returnResource' => true,
                ]);

                $qrImage = (new QRCode($options))->render($verifyUrl);
                $rawLogo = @imagecreatefrompng($logoPath);

                if ($qrImage instanceof \GdImage && $rawLogo instanceof \GdImage) {
                    $croppedLogo = @imagecropauto($rawLogo, IMG_CROP_THRESHOLD, 10, imagecolorat($rawLogo, 0, 0));
                    if ($croppedLogo instanceof \GdImage) {
                        imagedestroy($rawLogo);
                        $logo = $croppedLogo;
                    } else {
                        $logo = $rawLogo;
                    }

                    $qrW = imagesx($qrImage);
                    $qrH = imagesy($qrImage);
                    $logoW = imagesx($logo);
                    $logoH = imagesy($logo);

                    $maxBox = (int) round(min($qrW, $qrH) * 0.21);
                    $scale = min($maxBox / max(1, $logoW), $maxBox / max(1, $logoH));
                    $drawW = (int) round($logoW * $scale);
                    $drawH = (int) round($logoH * $scale);

                    $pad = (int) max(7, round(min($qrW, $qrH) * 0.02));
                    $badgeW = $drawW + ($pad * 2);
                    $badgeH = $drawH + ($pad * 2);
                    $badgeX1 = (int) floor(($qrW - $badgeW) / 2);
                    $badgeY1 = (int) floor(($qrH - $badgeH) / 2);
                    $badgeX2 = $badgeX1 + $badgeW;
                    $badgeY2 = $badgeY1 + $badgeH;
                    $radius = (int) max(8, round(min($badgeW, $badgeH) * 0.16));

                    imagealphablending($qrImage, true);
                    $white = imagecolorallocate($qrImage, 255, 255, 255);
                    $border = imagecolorallocate($qrImage, 203, 213, 225); // #CBD5E1

                    self::drawFilledRoundedRect($qrImage, $badgeX1 - 1, $badgeY1 - 1, $badgeX2 + 1, $badgeY2 + 1, $radius + 1, $border);
                    self::drawFilledRoundedRect($qrImage, $badgeX1, $badgeY1, $badgeX2, $badgeY2, $radius, $white);

                    $logoX = (int) floor(($qrW - $drawW) / 2);
                    $logoY = (int) floor(($qrH - $drawH) / 2);
                    imagecopyresampled($qrImage, $logo, $logoX, $logoY, 0, 0, $drawW, $drawH, $logoW, $logoH);

                    ob_start();
                    imagepng($qrImage);
                    $pngData = ob_get_clean();

                    imagedestroy($logo);
                    imagedestroy($qrImage);

                    if ($pngData !== false && $pngData !== '') {
                        return 'data:image/png;base64,' . base64_encode($pngData);
                    }
                }
            }

            return (new QRCode())->render($verifyUrl);
        } catch (\Throwable $e) {
            Log::warning('Styled QR generation failed: ' . $e->getMessage());
            return '';
        }
    }

    private static function drawFilledRoundedRect(\GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }
}
