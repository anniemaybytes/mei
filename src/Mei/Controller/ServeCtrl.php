<?php declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use Mei\Exception\GeneralException;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * Class ServeCtrl
 *
 * @package Mei\Controller
 */
class ServeCtrl extends BaseCtrl
{
    public static $legacySizes = [
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
        if (!isset($pathInfo['extension']) || !isset($pathInfo['filename'])) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $hashInfo = explode('-', $pathInfo['filename']);
        $info['name'] = $hashInfo[0];

        if (in_array($info['name'], array_keys(self::$legacySizes)) && count($hashInfo) == 2) {
            $info['width'] = self::$legacySizes[$hashInfo[0]][0];
            $info['height'] = self::$legacySizes[$hashInfo[0]][1];
            $info['crop'] = false;
            $info['name'] = $hashInfo[1];
        } elseif (isset($hashInfo[1])) {
            $dimensions = explode('x', $hashInfo[1]);
            if (count($dimensions) == 2) {
                $info['width'] = intval($dimensions[0]);
                $info['height'] = intval($dimensions[1]);
            }
            $info['crop'] = (isset($hashInfo[2]) && $hashInfo[2] == 'crop');
        }

        $fileEntity = $this->di->get('model.files_map')->getByFileName($info['name'] . '.' . $pathInfo['extension']);

        if (!$fileEntity) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $savePath = pathinfo($fileEntity->Key);
        $bindata = $this->di->get('utility.images')->getDataFromPath(
            $this->di->get('utility.images')->getSavePath(
                $savePath['filename'] . '.' . $this->di->get('utility.images')->mapExtension($savePath['extension'])
            )
        );
        if (!$bindata) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        // resize if necessary
        if (isset($info['width'])) {
            $image = $this->di->get('utility.images')->readImage($bindata);
            if (!$image) {
                throw new GeneralException('Unable to resize, possibly broken image?');
            }
            $bindata = $this->di->get('utility.images')->resizeImage(
                $image,
                $info['width'],
                $info['height'],
                $info['crop']
            );
        }

        $meta = $this->di->get('utility.images')->readImageData($bindata);

        if (!$meta) {
            throw new HttpNotFoundException($request, 'Image Not Found');
        }

        $eTag = md5($bindata);
        $path = $this->di->get('utility.images')->getSavePath(
            $savePath['filename'] . '.' . $this->di->get('utility.images')->mapExtension($savePath['extension'])
        );
        $timeStamp = Time::timeIsNonZero($fileEntity->UploadTime) ? $fileEntity->UploadTime->getTimestamp() : filemtime(
            $path
        );

        /** @var Response $response */
        $response = $response->withHeader('Content-Type', $meta['mime']);
        $response = $response->withHeader('Content-Length', $meta['size']);
        $response = $response->withHeader('Cache-Control', 'public, max-age=' . (strtotime('+30 days') - time()));
        $response = $response->withHeader('ETag', '"' . $eTag . '"');
        $response = $response->withHeader('Expires', date('r', strtotime('+30 days')));
        $response = $response->withHeader('Last-Modified', date('r', $timeStamp));

        if ($request->getHeader('If-None-Match') != $eTag) { // does not match etag?
            if (!isset($info['width'])) { // if no resize is taking place we can just ask nginx to stream file for us
                $response = $response->withHeader(
                    'Content-Security-Policy',
                    "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
                );
                $response = $response->withHeader(
                    'X-Accel-Redirect',
                    str_replace($this->config['site.images_root'], '/x-accel', $path)
                );
                return $response->write(
                    'Performing internal redirect...'
                ); // todo https://github.com/slimphp/Slim/issues/2924
            }
            return $response->write($bindata)->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
            );
        } else { // matches etag, return 304
            return $response->withStatus(304)->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; img-src data:; style-src 'unsafe-inline'"
            );
        }
    }
}
