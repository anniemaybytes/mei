<?php

declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use Mei\Model\FilesMap;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
     * @var ImageUtilities
     */
    private ImageUtilities $imageUtils;

    /**
     * @Inject
     * @var FilesMap
     */
    private FilesMap $filesMap;

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

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws HttpNotFoundException
     * @throws Exception
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

        $savePath = pathinfo($fileEntity->Key);
        $bindata = ImageUtilities::getDataFromPath(
            $this->imageUtils->getSavePath(
                $savePath['filename'] . '.' . $this->imageUtils::mapExtension($savePath['extension'])
            )
        );
        if (!$bindata) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        if (!$meta = $this->imageUtils->readImageData($bindata)) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        // resize if necessary
        if (isset($info['width'])) {
            $image = $this->imageUtils->openImage($bindata);
            $bindata = ImageUtilities::resizeImage(
                $image,
                $info['width'],
                $info['height'],
                $info['crop']
            );
        }

        $eTag = md5($bindata);
        $path = $this->imageUtils->getSavePath(
            $savePath['filename'] . '.' . $this->imageUtils::mapExtension($savePath['extension'])
        );
        $timeStamp = Time::timeIsNonZero($fileEntity->UploadTime) ? $fileEntity->UploadTime->getTimestamp() : filemtime(
            $path
        );

        $response = $response->withHeader('Content-Type', $meta['mime']);
        $response = $response->withHeader('Content-Length', (string)strlen($bindata));
        $response = $response->withHeader('Cache-Control', 'public, max-age=' . (strtotime('+30 days') - time()));
        $response = $response->withHeader('ETag', '"' . $eTag . '"');
        $response = $response->withHeader('Expires', date('r', strtotime('+30 days')));
        $response = $response->withHeader('Last-Modified', date('r', $timeStamp));

        if ($request->getHeader('If-None-Match')[0] !== $eTag) { // does not match etag?
            if (!isset($info['width'])) { // if no resize is taking place we can just ask nginx to stream file for us
                $response = $response->withHeader(
                    'Content-Security-Policy',
                    "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
                );
                return $response->withHeader(
                    'X-Accel-Redirect',
                    str_replace($this->config['site.images_root'], '/x-accel', $path)
                );
            } // otherwise we have to stream file ourselves
            return $response->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
            )->write($bindata);
        }

        // matches etag, return 304
        return $response->withStatus(304)->withHeader(
            'Content-Security-Policy',
            "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
        );
    }
}
