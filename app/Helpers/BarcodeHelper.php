<?php

namespace App\Helpers;

use Picqer\Barcode\BarcodeGeneratorPNG;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class BarcodeHelper
{
    /**
     * Generate barcode Code128 dan simpan ke storage
     */
    public static function generateBarcode(string $code, string $filename = null): string
    {
        $filename = $filename ?? 'barcodes/barcode_' . time() . '.png';
        $generator = new BarcodeGeneratorPNG();

        $barcode = $generator->getBarcode($code, $generator::TYPE_CODE_128);

        Storage::disk('public')->makeDirectory('barcodes');
        Storage::disk('public')->put($filename, $barcode);

        return Storage::url($filename);
    }

    /**
     * Generate QR code dan simpan ke storage
     */
    public static function generateQrCode(string $text, string $filename = null): string
    {
        $filename = $filename ?? 'qrcodes/qrcode_' . time() . '.png';
        Storage::disk('public')->makeDirectory('qrcodes');

        $qr = QrCode::format('png')
            ->size(160)
            ->generate($text, Storage::disk('public')->path($filename));

        return Storage::url($filename);
    }
}
