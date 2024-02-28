<?php

declare(strict_types=1);

namespace Mei\Controller;

use DI\Attribute\Inject;
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
    #[Inject]
    private FilesMap $filesMap;

    #[Inject]
    private Encryption $encryption;

    private static array $allowedUrlScheme = ['http', 'https'];

    /**
     * @throws HttpForbiddenException|JsonException
     */
    public function user(Request $request, Response $response, array $args): Response
    {
        if (!$this->encryption->hmacValid($request->getParam('t', ''), $request->getParam('s', ''))) {
            throw new HttpForbiddenException($request);
        }

        /**
         * Token specification:
         *  tvalid (required): unix timestamp this token is valid until
         *  mime (optional): specific mime-type from allowable range to restrict newly uploaded images
         *
         * Token might additionally contain additional arbitrary keys. Site owner can use the fact that on success
         * we return whole token, as a way to pass over some additonal data.
         *
         * @noinspection JsonEncodingApiUsageInspection
         **/
        $token = json_decode($this->encryption->decryptUrl($request->getParam('t', '')), true);
        if (Time::now()->getTimestamp() > ($token['tvalid'] ?? 0)) {
            throw new HttpForbiddenException($request);
        }

        $allowedTypes = ImageUtilities::$allowedTypes;
        if (@$token['mime']) {
            if (array_key_exists($token['mime'], ImageUtilities::$allowedTypes)) {
                $allowedTypes = [$token['mime'] => ImageUtilities::$allowedTypes[$token['mime']]];
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
        if (!$uploadedFiles = $request->getUploadedFiles()) {
            return $response
                ->withStatus(400)
                ->withJson(['success' => false, 'error' => 'No images to upload given']);
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

                if (!$file->getSize() || $file->getSize() > $this->config['images.max_filesize']) {
                    $errors[] = "File {$file->getClientFilename()} exceeds maximum filesize ({$this->config['images.max_filesize']})";
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

        $c = $this->encryption->encryptUrl(json_encode(['images' => $images, 'token' => $token], JSON_THROW_ON_ERROR));
        $s = $this->encryption->generateHmac($c);

        return $response
            ->withStatus(201)
            ->withJson(
                [
                    'success' => true,
                    'warnings' => $errors,
                    'callback' => ['content' => $c, 'sign' => $s]
                ]
            );
    }

    /**
     * @throws JsonException|HttpForbiddenException
     */
    public function api(Request $request, Response $response, array $args): Response
    {
        if (!$this->encryption->hmacValid($request->getParam('content', ''), $request->getParam('sign', ''))) {
            throw new HttpForbiddenException($request);
        }

        $images = [];
        $errors = [];

        $urls = json_decode(
            StringUtil::base64UrlDecode($request->getParam('content')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        if (empty($urls) || !is_array($urls)) {
            return $response
                ->withStatus(400)
                ->withJson(['success' => false, 'error' => 'No image URLs to upload given']);
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
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_HTTPHEADER => ["Host: $host"],
                    CURLOPT_MAXFILESIZE => $this->config['images.max_filesize'],
                    CURLOPT_NOPROGRESS => false,
                    CURLOPT_PROGRESSFUNCTION =>
                        fn($ch, $dt, $d, $ut, $u) => (int)($d > $this->config['images.max_filesize']),
                ]
            );

            $content = $curl->exec();
            $err = $curl->error();
            if ($err !== '') {
                $message = "URL $url encountered cURL error: $err";
                Debugger::log($message, Debugger::WARNING);
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
                Debugger::log($message, Debugger::WARNING);
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
        if (!$metadata['extension']) {
            throw new RuntimeException('Unable to process image without extension');
        }

        if (
            ($f = $this->filesMap->getByKey("{$metadata['hash']}.{$metadata['extension']}")) ||
            ($f = $this->filesMap->getByKey("{$metadata['md5']}.{$metadata['extension']}"))
        ) {
            $key = $f->Key;
        } else {
            $image = new ImagickUtility($bindata, $metadata);
            if ($this->config['images.strip_metadata']) {
                $bindata = $image->stripMeta()->getImagesBlob();
            }

            $key = "{$metadata['hash']}.{$metadata['extension']}";
            self::saveImage($bindata, ImageUtilities::getSavePath($key));
        }

        // generate new image entry in database and associate it with file on disk
        $name = StringUtil::generateRandomString(11);
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
