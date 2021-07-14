<?php

declare(strict_types=1);

namespace Mei\Controller;

use ImagickException;
use Mei\Model\FilesMap;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Imagick as ImagickUtility;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Class ServeCtrl
 *
 * @package Mei\Controller
 */
final class ServeCtrl extends BaseCtrl
{
    /** @Inject */
    private FilesMap $filesMap;

    private static array $allowedResizeRange = ['min' => 80, 'max' => 450];

    private const CSP_RULE = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

    /**
     * @throws HttpNotFoundException|ImagickException|HttpBadRequestException
     */
    public function serve(Request $request, Response $response, array $args): Response
    {
        $pathInfo = pathinfo($args['image']);
        if (!isset($pathInfo['extension'], $pathInfo['filename'])) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $hashInfo = explode('-', $pathInfo['filename']);
        if (isset($hashInfo[1])) {
            $dimensions = explode('x', $hashInfo[1]);
            if (count($dimensions) === 2) {
                $width = (int)$dimensions[0];
                $height = (int)$dimensions[1];
            }
            $crop = (isset($hashInfo[2]) && $hashInfo[2] === 'crop');
        }

        if (!$fileEntity = $this->filesMap->getByFileName("$hashInfo[0].{$pathInfo['extension']}")) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $bindata = self::getImageFromPath($fileEntity->Key);
        $metadata = ImageUtilities::getImageInfo($bindata);

        // resize if necessary
        if (isset($width, $height)) {
            if (
                min([$width, $height]) < self::$allowedResizeRange['min'] &&
                max([$width, $height]) > self::$allowedResizeRange['max']
            ) {
                throw new HttpBadRequestException($request);
            }

            $image = new ImagickUtility($bindata, $metadata);
            $bindata = $image->resize($width, $height, $crop ?? false)->getImagesBlob();
            /*
             * To avoid \Imagick object taking unnecessary memory while streaming, we destroy it here after we
             * fetch blob of image that we need.
             */
            unset($image);
        }

        $eTag = md5($bindata);
        $path = ImageUtilities::getSavePath($fileEntity->Key, false);
        $ts = Time::timeIsNonZero($fileEntity->UploadTime)
            ? $fileEntity->UploadTime->getTimestamp() : filemtime("{$this->config['images.directory']}$path");

        $response = $response->withHeader('Content-Type', $metadata['mime']);
        $response = $response->withHeader('Content-Length', (string)strlen($bindata));
        $response = $response->withHeader(
            'Cache-Control',
            'public, max-age=' . Time::epoch(Time::now()->add(Time::interval('1 month')))
        );
        $response = $response->withHeader('ETag', '"' . $eTag . '"');
        $response = $response->withHeader(
            'Expires',
            Time::rfc2822(Time::now()->add(Time::interval('1 month')))
        );
        $response = $response->withHeader('Last-Modified', Time::rfc2822(Time::fromEpoch($ts)));

        // does not match etag (might be empty array)
        if (@$request->getHeader('If-None-Match')[0] !== $eTag) {
            return $response->withHeader(
                'Content-Security-Policy',
                self::CSP_RULE
            )->write($bindata);
        }

        // matches etag, return 304
        return $response->withStatus(304)->withHeader(
            'Content-Security-Policy',
            self::CSP_RULE
        );
    }

    private static function getImageFromPath(string $filename): string
    {
        $file = ImageUtilities::getSavePath($filename);
        if (!is_file($file)) {
            throw new RuntimeException("Image missing from filesystem - $filename");
        }

        $contents = file_get_contents($file, false);
        if (!$contents) {
            throw new RuntimeException("Can't fetch contents of file - $filename");
        }

        return $contents;
    }
}
