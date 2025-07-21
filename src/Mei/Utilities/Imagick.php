<?php

declare(strict_types=1);

namespace Mei\Utilities;

use ImagickException;

/**
 * Class Imagick
 *
 * @package Mei\Utilities
 */
final class Imagick
{
    private \Imagick $image;

    /** @throws ImagickException */
    public function __construct(string $bindata, array $metadata)
    {
        $image = new \Imagick();
        $image->readImageBlob($bindata);
        $image->setImageFormat($metadata['extension'] ?? '');
        $image->setImageCompressionQuality(90);
        $image->setOption('png:compression-level', '9');
        $image->setOption('png:exclude-chunk', 'date,zTXt,tEXt,tIME');

        $this->image = $image;
    }

    public function __destruct()
    {
        $this->image->clear();
    }

    /** @throws ImagickException */
    public function getImagesBlob(): string
    {
        return $this->image->getImagesBlob();
    }

    /** @throws ImagickException */
    public function stripMeta(): self
    {
        foreach ($this->image->getImageProfiles('*', false) as $p) {
            if (!in_array($p, ['icc', 'icm'], true)) {
                $this->image->removeImageProfile($p);
            }
        }
        $this->image->setOption('png:include-chunk', 'none,cHRM,gAMA,iCCP,sBIT,sRGB,cICP,mDCV,cLLI,tRNS,bKGD,sPLT');

        return $this;
    }

    /**
     * Resizes image, either proportionally or by cropping.
     *
     * Note that this does not work for animated formats such as WEBP and GIF, as these require
     * working on each frame separately, which is rather expensive operation for GIFs that can be few MiB in size
     * and contain hundreds or thousands of frames.
     *
     * @throws ImagickException
     */
    public function makeThumbnail(int $maxWidth, int $maxHeight, bool $crop = false): self
    {
        if ($this->image->getNumberImages() > 1) {
            /*
             * Any attempt to resize animated image is fruitless and will usually result
             * in only single frame being resized while all others are left intact.
             */
            return $this;
        }

        $this->image->setOption('png:bit-depth', '8');

        $crop ?
            $this->image->cropThumbnailImage($maxWidth, $maxHeight) :
            $this->image->thumbnailImage($maxWidth, $maxHeight, true);

        $this->image->setImagePage(0, 0, 0, 0);

        return $this;
    }
}
