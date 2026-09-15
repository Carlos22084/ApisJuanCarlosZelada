<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudflareImageService
{
    public function editUpholstery(UploadedFile $image, string $prompt): string
    {
        $accountId = trim((string) config('services.cloudflare_ai.account_id'));
        $token = trim((string) config('services.cloudflare_ai.token'));
        $model = trim((string) config('services.cloudflare_ai.model'));

        if ($accountId === '' || $token === '') {
            throw new RuntimeException(
                'Cloudflare Workers AI no está configurado. Complete CLOUDFLARE_ACCOUNT_ID y CLOUDFLARE_API_TOKEN.'
            );
        }

        if ($model === '') {
            throw new RuntimeException('El modelo CLOUDFLARE_AI_MODEL no está configurado.');
        }

        [$inputPath, $temporaryPath] = $this->prepareReferenceImage($image);
        $stream = fopen($inputPath, 'rb');

        if ($stream === false) {
            $this->deleteTemporaryFile($temporaryPath);
            throw new RuntimeException('No fue posible leer la fotografía cargada.');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(180)
                ->retry(2, 800, throw: false)
                ->attach('input_image_0', $stream, 'furniture.jpg')
                ->post(
                    "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$model}",
                    [
                        'prompt' => $prompt,
                        'width' => (string) config('services.cloudflare_ai.width', 1024),
                        'height' => (string) config('services.cloudflare_ai.height', 1024),
                    ]
                );
        } finally {
            fclose($stream);
            $this->deleteTemporaryFile($temporaryPath);
        }

        if (!$response->successful() || $response->json('success') === false) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $encoded = $response->json('result.image');

        if (!is_string($encoded) || $encoded === '') {
            throw new RuntimeException('Cloudflare Workers AI no devolvió una imagen válida.');
        }

        if (str_contains($encoded, 'base64,')) {
            $encoded = explode('base64,', $encoded, 2)[1];
        }

        $decoded = base64_decode($encoded, true);

        if ($decoded === false || $decoded === '') {
            throw new RuntimeException('No fue posible decodificar la imagen devuelta por Cloudflare.');
        }

        return $decoded;
    }

    /**
     * Cloudflare admite imágenes de referencia de hasta 512x512 para FLUX.2.
     *
     * @return array{0: string, 1: string|null}
     */
    private function prepareReferenceImage(UploadedFile $image): array
    {
        $path = $image->getRealPath();
        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            throw new RuntimeException('La fotografía cargada no contiene una imagen válida.');
        }

        [$width, $height] = $dimensions;

        if ($width <= 512 && $height <= 512) {
            return [$path, null];
        }

        if (!extension_loaded('gd')) {
            throw new RuntimeException(
                'La fotografía supera 512x512. Active la extensión GD de PHP para reducirla automáticamente.'
            );
        }

        $source = match ($dimensions['mime'] ?? '') {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };

        if ($source === false) {
            throw new RuntimeException('No fue posible procesar la fotografía antes de enviarla a Cloudflare.');
        }

        $scale = min(512 / $width, 512 / $height);
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        $background = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $background);
        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        $temporaryPath = tempnam(sys_get_temp_dir(), 'cf-ai-');

        if ($temporaryPath === false || !imagejpeg($resized, $temporaryPath, 88)) {
            imagedestroy($source);
            imagedestroy($resized);
            throw new RuntimeException('No fue posible preparar la fotografía para Cloudflare.');
        }

        imagedestroy($source);
        imagedestroy($resized);

        return [$temporaryPath, $temporaryPath];
    }

    private function deleteTemporaryFile(?string $path): void
    {
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    private function errorMessage(Response $response): string
    {
        $message = $response->json('errors.0.message')
            ?? $response->json('error.message')
            ?? $response->json('message');

        return is_string($message) && $message !== ''
            ? "Cloudflare Workers AI: {$message}"
            : "Cloudflare Workers AI rechazó la solicitud (HTTP {$response->status()}).";
    }
}
