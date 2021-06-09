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

    /**
     * @param string $bindata
     * @param array $metadata
     *
     * @throws ImagickException
     */
    public function __construct(string $bindata, array $metadata)
    {
        $image = new \Imagick();
        $image->readImageBlob($bindata);
        $image->setImageFormat($metadata['extension']);
        $image->setImageCompressionQuality(90);
        $image->setOption('png:compression-level', '9');

        $this->image = $image;
    }

    public function __destruct()
    {
        $this->image->clear();
        $this->image->destroy();
    }

    /**
     * @return string
     * @throws ImagickException
     */
    public function getImagesBlob(): string
    {
        return $this->image->getImagesBlob();
    }

    /**
     * @throws ImagickException
     */
    public function stripExif(): self
    {
        $profiles = $this->image->getImageProfiles('icc', true);
        $this->image->stripImage();
        if (!empty($profiles)) {
            $this->image->profileImage('icc', $profiles['icc']);
        }
        return $this;
    }

    /**
     * Resizes image, either proportionally or by cropping.
     *
     * Note that this does not work for animated formats such as WEBP and GIF, as these require
     * working on each frame separately, which is rather expensive operation for GIFs that can be few MiB in size
     * and contain hundreds or thousands of frames.
     *
     * @param int $maxWidth
     * @param int $maxHeight
     * @param bool $crop
     *
     * @return self
     * @throws ImagickException
     */
    public function resize(int $maxWidth, int $maxHeight, bool $crop = false): self
    {
        if ($this->image->getNumberImages() > 1) {
            /*
             * Any attempt to resize animated image is fruitless and will usually result
             * in only single frame being resized while all others are left intact.
             */
            return $this;
        }

        $crop ?
            $this->image->cropThumbnailImage($maxWidth, $maxHeight) :
            $this->image->thumbnailImage($maxWidth, $maxHeight, true);

        $this->image->setImagePage(0, 0, 0, 0);

        return $this;
    }
}
