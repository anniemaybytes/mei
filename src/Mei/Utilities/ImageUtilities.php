<?php declare(strict_types=1);

namespace Mei\Utilities;

use Imagick;
use ImagickException;
use InvalidArgumentException;
use Slim\Container;
use Tracy\Debugger;

/**
 * Class ImageUtilities
 *
 * @package Mei\Utilities
 */
class ImageUtilities
{
    private $config;

    private static $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    private static $allowedResizeRange = ['min' => 20, 'max' => 1000];
    private static $allowedUrlScheme = ['http', 'https'];

    /**
     * ImageUtilities constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->config = $di['config'];
    }

    /**
     * @param string $extension
     *
     * @return string
     */
    public static function mapExtension(string $extension): string
    {
        $extension = strtolower($extension);
        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }

        return $extension;
    }

    /**
     * @param string $bindata
     *
     * @return array|null
     */
    public function readImageData(string $bindata): ?array
    {
        $data = @getimagesizefromstring($bindata);
        if (!$data || !isset($data['mime'])) {
            Debugger::log("Unable to read image info on binary data. Aborting.", DEBUGGER::WARNING);
            return null;
        }
        if (!array_key_exists($data['mime'], self::$allowedTypes)) {
            Debugger::log('Type ' . $data['mime'] . ' is not on allowed list. Aborting.', DEBUGGER::WARNING);
            return null;
        }

        return [
            'extension' => self::$allowedTypes[$data['mime']],
            'mime' => $data['mime'],
            'checksum' => hash('sha256', $bindata . $this->config['site.salt']),
            'checksum_legacy' => md5($bindata),
            'width' => $data[0],
            'height' => $data[1],
            'size' => strlen($bindata)
        ];
    }

    /**
     * @param string $name
     * @param int $depth
     *
     * @return string
     */
    public function getSavePath(string $name, int $depth = 3): string
    {
        if ($depth >= 32) {
            throw new InvalidArgumentException('Can not fetch save path that is >= 32 levels deep');
        }

        $dir = $this->config['site.images_root'];

        for ($i = 0; $i < $depth; ++$i) {
            $dir .= '/' . $name[$i];
        }

        return $dir . '/' . $name;
    }

    /**
     * @param string $url
     *
     * @return null|string
     */
    public function getDataFromUrl(string $url): ?string
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            Debugger::log('Invalid URL (' . $url . ') was provied for getDataFromUrl. Aborting.', DEBUGGER::WARNING);
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($scheme, self::$allowedUrlScheme)) {
            $curl = new Curl($url);
            $curl->setoptArray(
                [
                    CURLOPT_ENCODING => 'UTF-8',
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_HTTPHEADER => ['Host: ' . $host],
                ]
            );

            $content = $curl->exec();
            $content_length = (int)$curl->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $respcode = (int)$curl->getInfo(CURLINFO_HTTP_CODE);
            unset($curl);

            if ($content_length > $this->config['site.max_filesize'] || $content_length <= 0 || $respcode >= 400) {
                Debugger::log(
                    "Aborting getDataFromUrl on $url with size $content_length and response $respcode",
                    DEBUGGER::WARNING
                );
                return null;
            }
            if ($content) {
                return $content;
            }
            Debugger::log(
                "No data received from $url with size $content_length and response $respcode. Aborting.",
                DEBUGGER::WARNING
            );
        }

        return null;
    }

    /**
     * @param string $path
     *
     * @return null|string
     */
    public function getDataFromPath(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path, false);
        if ($contents) {
            return $contents;
        }

        Debugger::log("Can't fetch contents of file from $path. Aborting.", DEBUGGER::WARNING);
        return null;
    }

    /**
     * @param string $bindata
     *
     * @return null|Imagick
     */
    public function readImage(string $bindata): ?Imagick
    {
        $data = self::readImageData($bindata);
        if (!$data) {
            return null;
        }

        try {
            $image = new Imagick();
            $image->readImageBlob($bindata);
            $image->setImageFormat($data['extension']);
            $image->setImageCompressionQuality(90);
            $image->setOption('png:compression-level', '9');
            return $image;
        } catch (ImagickException  $e) {
            Debugger::log($e, DEBUGGER::EXCEPTION);
            return null;
        }
    }

    /**
     * @param string|null $bindata
     * @param string $savePath
     * @param bool $stripExif
     *
     * @return bool
     */
    public function saveData(?string $bindata, string $savePath, bool $stripExif = true): bool
    {
        if (file_exists($savePath)) {
            return true;
        } // let code assume it succeeded

        if ($stripExif && $bindata) {
            $bindata = $this->stripImage($this->readImage($bindata)); // strip image of EXIF, profiles and comments
        }
        if (!$bindata) {
            return false;
        }

        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            if (mkdir($dir, 0750, true) === false) {
                Debugger::log("Unable to create directory $dir.", DEBUGGER::ERROR);
            }
        }
        if (file_put_contents($savePath, $bindata) === false) {
            Debugger::log("Unable to save binary data on $savePath.", DEBUGGER::ERROR);
        }
        if (chmod($savePath, 0640) === false) {
            Debugger::log("Unable to set mode on $savePath.", DEBUGGER::ERROR);
            return false;
        }
        return true;
    }

    /**
     * @param Imagick $image
     *
     * @return null|string
     */
    private function stripImage(Imagick $image): ?string
    {
        try {
            $profiles = $image->getImageProfiles("icc", true);
            $image->stripImage();
            if (!empty($profiles)) {
                $image->profileImage("icc", $profiles['icc']);
            }
            return $image->getImagesBlob();
        } catch (ImagickException $e) {
            Debugger::log($e, DEBUGGER::EXCEPTION);
            return null;
        }
    }

    /**
     * @param Imagick $image
     * @param int $maxWidth
     * @param int $maxHeight
     * @param bool $crop
     *
     * @return null|string
     */
    public function resizeImage(Imagick $image, int $maxWidth, int $maxHeight, bool $crop = false): ?string
    {
        // check dimensions are valid
        if (min([$maxWidth, $maxHeight]) < self::$allowedResizeRange['min'] ||
            max([$maxWidth, $maxHeight]) > self::$allowedResizeRange['max']) {
            Debugger::log(
                "Dimmensions $maxWidth x $maxHeight are outside acceptable range. Aborting.",
                DEBUGGER::WARNING
            );
            return null;
        }

        try {
            if ($crop) {
                $image->cropThumbnailImage($maxWidth, $maxHeight);
            } else {
                $image->thumbnailImage($maxWidth, $maxHeight, true);
            }
            $image->setImagePage(0, 0, 0, 0);
            return $image->__toString();
        } catch (ImagickException $e) {
            Debugger::log($e, DEBUGGER::EXCEPTION);
            return null;
        }
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        return rename($path, $this->config['site.deleted_root'] . '/' . basename($path));
    }

    /**
     * @param array $urls
     */
    public function clearCacheForImage(array $urls)
    {
        if (is_array($urls) && $this->config['cloudflare.enabled']) { // domain present
            $curl = new Curl(
                'https://api.cloudflare.com/client/v4/zones/' . $this->config['cloudflare.zone'] . '/purge_cache'
            );
            $curl->setoptArray(
                [
                    CURLOPT_ENCODING => 'UTF-8',
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => false,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HTTPHEADER => [
                        'Host: api.cloudflare.com',
                        'Authorization: Bearer ' . $this->config['cloudflare.api'],
                        'Content-Type: application/json'
                    ],
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_POSTFIELDS => json_encode(["files" => $urls])
                ]
            );

            $result = $curl->exec();
            unset($curl);

            $result = json_decode($result, true);
            if (!$result['success']) { // log it as error since we want bluescreen for debugging
                Debugger::log('Failed to clear cache for ' . implode(', ', $urls), DEBUGGER::ERROR);
            }
        }
    }
}
