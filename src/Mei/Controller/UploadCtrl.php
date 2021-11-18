<?php

declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use ImagickException;
use JsonException;
use Mei\Model\FilesMap;
use Mei\Utilities\Curl;
use Mei\Utilities\Encryption;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Imagick as ImagickUtility;
use Mei\Utilities\StringUtil;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Exception\HttpForbiddenException;
use Tracy\Debugger;

/**
 * Class UploadCtrl
 *
 * @package Mei\Controller
 */
final class UploadCtrl extends BaseCtrl
{
    /** @Inject */
    private FilesMap $filesMap;

    /** @Inject */
    private Encryption $encryption;

    private static array $allowedUrlScheme = ['http', 'https'];

    /**
     * @throws HttpForbiddenException|JsonException
     */
    public function user(Request $request, Response $response, array $args): Response
    {
        /**
         * Token specification:
         *  mime (optional): specific mime-type from allowable range to restrict newly uploaded images
         *  tvalid (required): valid until
         *  referer (required): url for redirection after upload
         *
         * Token might additionally contain additional arbitrary keys. Site owner can use the fact that on success
         * we return whole token, as a way to pass over some additonal data (such as userId) to endpoint indicated by
         * value given in `referer` key.
         *
         * @noinspection JsonEncodingApiUsageInspection
         **/
        $token = json_decode($this->encryption->decryptString($request->getParam('token', '')), true);
        if (time() > ($token['tvalid'] ?? 0)) {
            throw new HttpForbiddenException($request);
        }

        $referer = $token['referer'] ?? '';
        if ($referer === '' || !filter_var($referer, FILTER_VALIDATE_URL)) {
            return $response
                ->withStatus(400)
                ->withJson(['success' => false, 'error' => 'No valid Referer given']);
        }

        $allowedTypes = ImageUtilities::$allowedTypes;
        if (@$token['mime']) {
            if (array_key_exists($token['mime'], ImageUtilities::$allowedTypes)) {
                $allowedTypes = [
                    $token['mime'] => ImageUtilities::$allowedTypes[$token['mime']]
                ];
            } else {
                return $response
                    ->withStatus(400)
                    ->withJson(
                        ['success' => false, 'error' => "Unacceptable MIME type restriction ({$token['mime']})"]
                    );
            }
        }

        $images = [];
        $errors = [];
        $uploadedFiles = $request->getUploadedFiles();
        if (!$uploadedFiles) {
            return $response
                ->withStatus(400)
                ->withJson(['success' => false, 'error' => 'Empty request (no images supplied)']);
        }

        /*
         * $uploadedFiles will hold an array with each element representing single upload
         * (in case of html form, each input element is one upload, input element can have multiple files)
         * each element is an array of UploadedFileInterface items
         */
        foreach ($uploadedFiles as $leaf) {
            if (!is_array($leaf)) {
                continue;
            }
            foreach ($leaf as $file) {
                /** @var UploadedFileInterface $file */
                if ($file->getError() === 4) {
                    continue;
                }

                if (!$file->getSize() || $file->getSize() > $this->config['app.max_filesize']) {
                    $errors[] = "File {$file->getClientFilename()} exceeds maximum filesize ({$this->config['app.max_filesize']})";
                    continue;
                }

                try {
                    $bindata = $file->getStream()->getContents();
                    $metadata = ImageUtilities::getImageInfo($bindata);
                    if (!array_key_exists($metadata['mime'], $allowedTypes)) {
                        $errors[] = "File {$file->getClientFilename()} has MIME type ({$metadata['mime']}) which is not allowable";
                        continue;
                    }
                    $images[] = $this->processImage($bindata, $metadata);
                } catch (Exception $e) {
                    Debugger::log($e, Debugger::EXCEPTION);
                    $errors[] = "Encountered error while processing image {$file->getClientFilename()}";
                }
            }
        }

        if (empty($images)) {
            return $response
                ->withStatus(415)
                ->withJson(['success' => false, 'error' => 'No valid images were processed', 'warnings' => $errors]);
        }

        $token['images'] = $images;
        $token = $this->encryption->encryptUrl(json_encode($token, JSON_THROW_ON_ERROR));
        $qs = (parse_url($referer, PHP_URL_QUERY) ? '&' : '?') . "token=$token";

        return $response
            ->withStatus(303)
            ->withHeader('Location', $referer . $qs)
            ->withJson(['success' => true, 'warnings' => $errors]);
    }

    /**
     * @throws HttpForbiddenException
     */
    public function api(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getParam('auth', '');
        if (!hash_equals($auth, $this->config['api.secret'])) {
            throw new HttpForbiddenException($request);
        }

        $images = [];
        $errors = [];
        $urls = $request->getParam('urls');
        if (is_string($urls)) {
            $urls = [$urls];
        }
        if (empty($urls)) {
            return $response
                ->withStatus(400)
                ->withJson(['success' => false, 'error' => 'Empty request (no images supplied)']);
        }

        foreach ($urls as $url) {
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid URL $url provided (FILTER_VALIDATE_URL)";
                continue;
            }

            $scheme = parse_url($url, PHP_URL_SCHEME);
            $host = parse_url($url, PHP_URL_HOST);
            if (!in_array($scheme, self::$allowedUrlScheme, true)) {
                $errors[] = "Scheme $scheme of URL $url is not allowed to be fetched from";
                continue;
            }

            $curl = new Curl($url);
            $curl->setoptArray(
                [
                    CURLOPT_ENCODING => 'UTF-8',
                    CURLOPT_USERAGENT => ImageUtilities::USER_AGENT,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_HTTPHEADER => ["Host: $host"],
                    CURLOPT_MAXFILESIZE => $this->config['app.max_filesize'],
                    CURLOPT_NOPROGRESS => false,
                    CURLOPT_PROGRESSFUNCTION => (
                    fn($ch, $dt, $d, $ut, $u) => (int)($d > $this->config['app.max_filesize'])
                    ),
                ]
            );

            $content = $curl->exec();
            $err = $curl->error();
            if ($err !== '') {
                $message = "URL $url encountered cURL error: $err";
                Debugger::log($message, DEBUGGER::WARNING);
                $errors[] = $message;
                continue;
            }

            $cl = (int)$curl->getInfo(CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $respcode = (int)$curl->getInfo(CURLINFO_HTTP_CODE);
            unset($curl);

            if ($respcode !== 200) {
                $message = "Received non-success response $respcode from $url";
                $errors[] = $message;
                continue;
            }
            if (!$content) {
                $message = "No data received from $url" . ($cl > 0 ? "(expected $cl bytes)" : "") . " with response $respcode";
                $errors[] = $message;
                Debugger::log($message, DEBUGGER::WARNING);
                continue;
            }

            try {
                $metadata = ImageUtilities::getImageInfo($content);
                if (!array_key_exists($metadata['mime'], ImageUtilities::$allowedTypes)) {
                    $errors[] = "File $url has MIME type ({$metadata['mime']} which is not allowable";
                    continue;
                }
                $images[] = $this->processImage($content, $metadata);
            } catch (Exception $e) {
                Debugger::log($e, Debugger::EXCEPTION);
                $errors[] = "Encountered error while processing image $url";
            }
        }

        if (empty($images)) {
            return $response
                ->withStatus(415)
                ->withJson(['success' => false, 'error' => 'No valid images were processed', 'warnings' => $errors]);
        }

        return $response
            ->withStatus(201)
            ->withJson(['success' => true, 'images' => $images, 'warnings' => $errors]);
    }

    /**
     * @throws ImagickException
     * @noinspection PhpSameParameterValueInspection
     */
    private function processImage(string $bindata, array $metadata, int $protected = 0): string
    {
        $found = $isLegacy = false;
        if ($this->filesMap->getByKey("{$metadata['hash']}.{$metadata['extension']}")) {
            $found = true;
            $isLegacy = false;
        } elseif ($this->filesMap->getByKey("{$metadata['md5']}.{$metadata['extension']}")) {
            $found = true;
            $isLegacy = true;
        }

        $key = ($isLegacy ? $metadata['md5'] : $metadata['hash']) . ".{$metadata['extension']}";
        if (!$found) {
            // verify image is valid by trying to open it using ImageMagick, will throw if necessary
            $image = new ImagickUtility($bindata, $metadata);

            // strip EXIF is requested
            if ($this->config['app.strip_exif']) {
                $bindata = $image->stripExif()->getImagesBlob();
            }

            // flush file to disk
            self::saveImage($bindata, ImageUtilities::getSavePath($key));
        }

        // generate new image entry in database and associate it with file on disk
        $name = StringUtil::generateRandomString($this->config['images.name_len']);
        $newImage = $this->filesMap->createEntity(
            [
                'Key' => $key,
                'FileName' => "$name.{$metadata['extension']}",
                'Protected' => $protected,
                'UploadTime' => Time::now()
            ]
        );
        $this->filesMap->save($newImage);

        return "$name.{$metadata['extension']}";
    }

    /**
     * @throws RuntimeException
     */
    private static function saveImage(string $bindata, string $path): void
    {
        if (file_exists($path)) {
            return;
        }

        $dir = dirname($path);
        if (!@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException("Unable to create directory $dir");
        }
        if (file_put_contents($path, $bindata) === false) {
            throw new RuntimeException("Unable to save binary data on $path");
        }
        if (chmod($path, 0640) === false) {
            throw new RuntimeException("Unable to set mode on $path");
        }
    }
}
