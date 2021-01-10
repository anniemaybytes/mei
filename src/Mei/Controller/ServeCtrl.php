<?php

declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use ImagickException;
use Mei\Model\FilesMap;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Imagick as ImagickUtility;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;

/**
 * Class ServeCtrl
 *
 * @package Mei\Controller
 */
final class ServeCtrl extends BaseCtrl
{
    /**
     * @Inject
     * @var FilesMap
     */
    private FilesMap $filesMap;

    /**
     * @var array
     */
    private static array $allowedResizeRange = ['min' => 80, 'max' => 450];

    /**
     * @var array
     */
    public static array $legacySizes = [
        'small' => [80, 150],
        'front' => [200, 150],
        'imgupl' => [120, 100],
        'coverflow' => [120, 100],
        'imageupl' => [450, 450],
        'groupimg' => [200, 400],
    ];

    private const CSP_RULE = "default-src 'none'; style-src 'unsafe-inline'; sandbox";

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws HttpNotFoundException|ImagickException|Exception
     */
    public function serve(Request $request, Response $response, array $args): Response
    {
        $pathInfo = pathinfo($args['img']);
        if (!isset($pathInfo['extension'], $pathInfo['filename'])) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $hashInfo = explode('-', $pathInfo['filename']);
        $info['name'] = $hashInfo[0];

        if (array_key_exists($info['name'], self::$legacySizes) && count($hashInfo) === 2) {
            $info['width'] = self::$legacySizes[$hashInfo[0]][0];
            $info['height'] = self::$legacySizes[$hashInfo[0]][1];
            $info['crop'] = false;
            $info['name'] = $hashInfo[1];
        } elseif (isset($hashInfo[1])) {
            $dimensions = explode('x', $hashInfo[1]);
            if (count($dimensions) === 2) {
                $info['width'] = (int)$dimensions[0];
                $info['height'] = (int)$dimensions[1];
            }
            $info['crop'] = (isset($hashInfo[2]) && $hashInfo[2] === 'crop');
        }

        if (!$fileEntity = $this->filesMap->getByFileName($info['name'] . '.' . $pathInfo['extension'])) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $bindata = self::getImageFromPath($fileEntity->Key);
        $metadata = ImageUtilities::getImageInfo($bindata);

        // resize if necessary
        if (
            isset($info['width']) &&
            min([$info['width'], $info['height']]) >= self::$allowedResizeRange['min'] &&
            max([$info['width'], $info['height']]) <= self::$allowedResizeRange['max']
        ) {
            $image = new ImagickUtility($bindata);
            $bindata = $image->resize($info['width'], $info['height'], $info['crop'])->getImagesBlob();
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
        $response = $response->withHeader('Cache-Control', 'public, max-age=' . (strtotime('+30 days') - time()));
        $response = $response->withHeader('ETag', '"' . $eTag . '"');
        $response = $response->withHeader('Expires', date('r', strtotime('+30 days')));
        $response = $response->withHeader('Last-Modified', date('r', $ts));

        // does not match etag (might be empty array)
        if (@$request->getHeader('If-None-Match')[0] !== $eTag) {
            if (!isset($info['width'])) {
                // if no resize is taking place we can just ask nginx to stream file for us
                $response = $response->withHeader(
                    'Content-Security-Policy',
                    self::CSP_RULE
                );
                return $response->withHeader('X-Accel-Redirect', "/x-accel$path");
            }
            // otherwise we have to stream file ourselves
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

    /**
     * @param string $filename
     *
     * @return string
     */
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
