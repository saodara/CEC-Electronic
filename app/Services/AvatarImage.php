<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class AvatarImage
{
    public const SIZE = 256;

    /**
     * Turn an upload into a small square JPEG. Re-encoding also drops the photo's
     * metadata, such as the GPS location phones embed. Returns null if unreadable.
     */
    public function toJpeg(UploadedFile $file): ?string
    {
        $contents = file_get_contents($file->getRealPath());
        $source = $contents === false ? false : @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $source = $this->applyOrientation($source, $file);
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $avatar = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($avatar, 0, 0, imagecolorallocate($avatar, 255, 255, 255));
        imagecopyresampled(
            $avatar, $source,
            0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2),
            self::SIZE, self::SIZE, $side, $side
        );

        ob_start();
        imagejpeg($avatar, null, 85);

        return ob_get_clean() ?: null;
    }

    /**
     * Phones save photos sideways and record the rotation in EXIF.
     */
    private function applyOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
