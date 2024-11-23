<?php

declare(strict_types=1);

namespace Mei\Controller;

use DI\Attribute\Inject;
use ImagickException;
use Mei\Model\FilesMap;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Imagick as ImagickUtility;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Tracy\Debugger;

/**
 * Class ServeCtrl
 *
 * @package Mei\Controller
 */
final class ServeCtrl extends BaseCtrl
{
    private const string CACHE_MAX_AGE = "1 month";
    private const string CSP_RULE = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

    #[Inject]
    private FilesMap $filesMap;

    private static array $allowedResizeRange = ['min' => 80, 'max' => 450];

    /**
     * @throws ImagickException
     * @throws HttpNotFoundException|HttpBadRequestException
     */
    public function serve(Request $request, Response $response, array $args): Response
    {
        $pathInfo = pathinfo($args['image']);
        if (!isset($pathInfo['extension'])) {
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

        if (!array_key_exists($metadata['content_type'], ImageUtilities::$allowedTypes)) {
            throw new HttpForbiddenException($request, 'Unallowable MIME type');
        }

        // resize if necessary
        if (isset($width, $height)) {
            if (
                min([$width, $height]) < self::$allowedResizeRange['min'] &&
                max([$width, $height]) > self::$allowedResizeRange['max']
            ) {
                throw new HttpBadRequestException($request);
            }

            try {
                $image = new ImagickUtility($bindata, $metadata);
                $bindata = $image->resize($width, $height, $crop ?? false)->getImagesBlob();
            } catch (ImagickException $e) {
                Debugger::log($e, Debugger::WARNING);
            } finally {
                /*
                 * To avoid \Imagick object taking unnecessary memory while streaming, we destroy it here after we
                 * fetch blob of image that we need.
                 */
                unset($image);
            }
        }

        $etag = '"' . hash('xxh64', $bindata) . '"';
        $mtime = Time::timeIsNonZero($fileEntity->UploadTime) ?
            $fileEntity->UploadTime->getTimestamp() : filemtime(ImageUtilities::getSavePath($fileEntity->Key));
        $expire = Time::now()->add(Time::interval(self::CACHE_MAX_AGE));

        $response = $response->withHeader('Content-Type', $metadata['content_type']);
        $response = $response->withHeader('Content-Length', (string)strlen($bindata));
        $response = $response->withHeader(
            'Cache-Control',
            'public, max-age=' . ($expire->getTimestamp() - Time::now()->getTimestamp())
        );
        $response = $response->withHeader('ETag', $etag);
        $response = $response->withHeader('Expires', Time::toRfc2822($expire));
        $response = $response->withHeader('Last-Modified', Time::toRfc2822(Time::fromEpoch($mtime)));
        $response = $response->withHeader('Content-Security-Policy', self::CSP_RULE);

        if ($request->getHeaderLine('If-None-Match') === $etag) {
            return $response->withStatus(304);
        }

        return $response->write($bindata);
    }

    private static function getImageFromPath(string $filename): string
    {
        $file = ImageUtilities::getSavePath($filename);
        if (!is_file($file)) {
            throw new RuntimeException("Image missing from filesystem - $filename");
        }

        if (!$contents = file_get_contents($file)) {
            throw new RuntimeException("Can't fetch contents of file - $filename");
        }

        return $contents;
    }
}
