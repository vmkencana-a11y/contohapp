<?php

namespace App\Services;

/**
 * KYC Image Processing Service
 * 
 * Handles image validation, resizing, compression, and EXIF removal.
 * Uses GD library (built-in PHP) for compatibility.
 */
class KycImageService
{
    /**
     * Maximum dimension (width or height).
     */
    private const MAX_DIMENSION = 1920;

    /**
     * Minimum dimension for KYC validity.
     */
    private const MIN_DIMENSION = 600;

    /**
     * JPEG quality (0-100).
     */
    private const JPEG_QUALITY = 85;

    /**
     * Process image from raw binary content.
     */
    public function processFromContent(string $content): string
    {
        $image = @imagecreatefromstring($content);
        
        if ($image === false) {
            throw new \RuntimeException('Failed to load image from content');
        }

        $this->validateDimensions($image);
        $image = $this->resize($image);
        
        return $this->compress($image);
    }

    /**
     * Validate image dimensions.
     */
    private function validateDimensions(\GdImage $image): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < self::MIN_DIMENSION && $height < self::MIN_DIMENSION) {
            throw new \InvalidArgumentException(
                "Image too small. Minimum dimension: " . self::MIN_DIMENSION . "px"
            );
        }
    }

    /**
     * Resize image if larger than max dimension.
     */
    private function resize(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Check if resize needed
        if ($width <= self::MAX_DIMENSION && $height <= self::MAX_DIMENSION) {
            return $image;
        }

        // Calculate new dimensions (maintain aspect ratio)
        if ($width > $height) {
            $newWidth = self::MAX_DIMENSION;
            $newHeight = (int) round($height * (self::MAX_DIMENSION / $width));
        } else {
            $newHeight = self::MAX_DIMENSION;
            $newWidth = (int) round($width * (self::MAX_DIMENSION / $height));
        }

        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        // Resample
        imagecopyresampled(
            $resized,
            $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        // Free original
        imagedestroy($image);

        return $resized;
    }

    /**
     * Compress image to JPEG and return binary content.
     * This also strips all EXIF data.
     */
    private function compress(\GdImage $image): string
    {
        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);
        $content = ob_get_clean();

        imagedestroy($image);

        if ($content === false || $content === '') {
            throw new \RuntimeException('Failed to compress image');
        }

        return $content;
    }

    /**
     * Get image info from binary content.
     * 
     * @return array{width: int, height: int, mime: string}
     */
    public function getImageInfo(string $content): array
    {
        $info = @getimagesizefromstring($content);
        
        if ($info === false) {
            throw new \RuntimeException('Failed to get image info');
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'mime' => $info['mime'],
        ];
    }
}
