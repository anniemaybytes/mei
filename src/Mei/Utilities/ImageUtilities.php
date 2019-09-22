<?php
namespace Mei\Utilities;

use Exception;
use Imagick;

class ImageUtilities
{
    private $config;

    private static $allowedTypes = array ('image/jpeg' => 'jpg', 'image/gif'  => 'gif', 'image/png'  => 'png');
    private static $allowedResizeRange = array('min' => 20, 'max' => 1000);
    private static $allowedUrlScheme = array('http', 'https');

    public function __construct($di)
    {
        $this->config = $di['config'];
    }

    public static function mapExtension($extension)
    {
        $extension = strtolower($extension);
        if ($extension == 'jpeg') $extension = 'jpg';

        return $extension;
    }

    public function readImageData($bindata)
    {
        $data = @getimagesizefromstring($bindata);
        if (!$data || !isset($data['mime'])) return false;
        if (!array_key_exists($data['mime'], self::$allowedTypes)) return false;

        $mime = $data['mime'];

        $data = array(
            'extension'        => self::$allowedTypes[$mime],
            'mime'             => $mime,
            'checksum'         => hash('sha256', $bindata . $this->config['site.salt']),
            'checksum_legacy'  => md5($bindata),
            'width'            => $data[0],
            'height'           => $data[1],
            'size'             => strlen($bindata)
        );

        return $data ? $data : false;
    }

    public function getSavePath($name, $depth = 3)
    {
        if ($depth >= 32) return false;

        $dir = $this->config['site.images_root'];

        for ($i = 0; $i < $depth; ++$i) {
            $dir .= '/' . $name[$i];
        }

        return $dir . '/' . $name;
    }

    public function getDataFromUrl($url)
    {
        if(!$url || !filter_var($url, FILTER_VALIDATE_URL)) return false;

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (in_array($scheme, self::$allowedUrlScheme))
        {
            $curl = new Curl();
            $curl->setoptArray(array(
                CURLOPT_URL => $url,
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
                CURLOPT_HTTPHEADER => array('Host: '.$host),
            ));

            $content = $curl->exec();
            $content_length = $curl->getinfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $respcode = $curl->getinfo(CURLINFO_HTTP_CODE);
            unset($curl);

            if (!$content_length) return false;
            if (intval($respcode) >= 400) return false;

            if ($content_length > $this->config['site.max_filesize'] || $content_length < 0) return false;

            if ($content) return $content;
        }

        return false;
    }

    public function getDataFromPath($path)
    {
        if (!$path) return false;

        if (!is_file($path)) return false;

        $contents = @file_get_contents($path, false);
        if ($contents) return $contents;

        return false;
    }

    /**
     * @param $bindata
     * @return bool|Imagick
     */
    public function readImage($bindata)
    {
        $data = self::readImageData($bindata);
        if (!$data) return false;

        try {
            $image = new Imagick();
            $image->readImageBlob($bindata);
            $image->setImageFormat($data['extension']);
            $image->setImageCompressionQuality(90);
            $image->setOption('png:compression-level', 9);
            return $image;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function saveData($bindata, $savePath, $stripExif = true)
    {
        if (!$savePath || !$bindata) return false;
        if(file_exists($savePath)) return true; // let code assume it succeeded

        if($stripExif) {
            $bindata = $this->stripImage($this->readImage($bindata)); // strip image of EXIF, profiles and comments
        }
        if($bindata instanceof Imagick) $bindata = $bindata->getImagesBlob();
        if(!$bindata) return false;

        $dir = dirname($savePath);
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        file_put_contents($savePath, $bindata);
        if(!chmod($savePath, 0640)) return false;
        return true;
    }

    /**
     * @param Imagick $image
     * @return bool|Imagick
     */
    private function stripImage($image)
    {
        if (!$image) return false;

        try {
            $profiles = $image->getImageProfiles("icc", true);
            $image->stripImage();
            if(!empty($profiles)) {
                $image->profileImage("icc", $profiles['icc']);
            }
            return $image;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param Imagick $image
     * @param $maxWidth
     * @param $maxHeight
     * @param bool $crop
     * @return bool|Imagick
     */
    public function resizeImage($image, $maxWidth, $maxHeight, $crop = false)
    {
        if (!$image) return false;

        // check dimensions are valid
        if (!is_int($maxWidth) || !is_int($maxHeight) ||
            min(array($maxWidth, $maxHeight)) < self::$allowedResizeRange['min'] ||
            max(array($maxWidth, $maxHeight)) > self::$allowedResizeRange['max']) {
            return false;
        }

        try {
            if ($crop) {
                $image->cropThumbnailImage($maxWidth, $maxHeight);
            }
            else {
                $image->thumbnailImage($maxWidth, $maxHeight, true);
            }
            $image->setImagePage(0, 0, 0, 0);
            return $image;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function deleteImage($path)
    {
        return rename($path, $this->config['site.deleted_root'] . '/' . basename($path));
    }

    public function clearCacheForImage($urls)
    {
        if (is_array($urls) && $this->config['cloudflare.enabled']) { // domain present
            $curl = new Curl();
            $curl->setoptArray(array(
                CURLOPT_URL => 'https://api.cloudflare.com/client/v4/zones/' . $this->config['cloudflare.zone'] . '/purge_cache',
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
            ));

            $result = $curl->exec();
            unset($curl);

            $result = json_decode($result, true);
            if (!$result['success']) error_log('Failed to clear cache for ' . implode(', ', $urls));
        }
    }
}
