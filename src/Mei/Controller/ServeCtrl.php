<?php

namespace Mei\Controller;

class ServeCtrl extends \Mei\Controller\BaseCtrl
{
    public static $legacySizes = array(
        'small'     => array(80 , 150),
        'front'     => array(200, 150),
        'imgupl'    => array(120, 100),
        'coverflow' => array(120, 100),
        'imageupl'  => array(450, 450),
        'groupimg'  => array(200, 400),
    );

    /**
     * @param \Slim\Http\Request $request
     * @param \Slim\Http\Response $response
     * @param $args
     * @return \Slim\Http\Response
     * @throws \Mei\Exception\NotFound
     */
    public function serve($request, $response, $args)
    {
        $pathInfo = pathinfo($args['img']);
        if (!isset($pathInfo['extension']) || !isset($pathInfo['filename'])) {
            throw new \Mei\Exception\NotFound('Image Not Found');
        }

        $hashInfo = explode('-', $pathInfo['filename']);
        $info['name'] = $hashInfo[0];

        if (in_array($info['name'], array_keys(self::$legacySizes)) && count($hashInfo) == 2) {
            $info['width'] = self::$legacySizes[$hashInfo[0]][0];
            $info['height'] = self::$legacySizes[$hashInfo[0]][1];
            $info['crop'] = false;
            $info['name'] = $hashInfo[1];
        }
        elseif (isset($hashInfo[1])) {
            $dimensions = explode('x', $hashInfo[1]);
            if (count($dimensions) == 2) {
                $info['width']  = intval($dimensions[0]);
                $info['height'] = intval($dimensions[1]);
            }
            $info['crop'] = (isset($hashInfo[2]) && $hashInfo[2] == 'crop');
        }

        $fileEntity = $this->di['model.files_map']->getByFileName($info['name'] . '.' . $pathInfo['extension']);

        if (!$fileEntity) {
            throw new \Mei\Exception\NotFound('Image Not Found');
        }

        $savePath = pathinfo($fileEntity->Key);
        $bindata = $this->di['utility.images']->getDataFromPath($this->di['utility.images']->getSavePath($savePath['filename'] . '.' . $this->di['utility.images']->mapExtension($savePath['extension'])));
        if (!$bindata) {
            throw new \Mei\Exception\NotFound('Image Not Found');
        }

        // resize if necessary
        if (isset($info['width'])) {
            $bindata = $this->di['utility.images']->resizeImage($this->di['utility.images']->readImage($bindata), $info['width'], $info['height'], $info['crop']);
        }

        $meta = $this->di['utility.images']->readImageData($bindata);

        if (!$meta) {
            throw new \Mei\Exception\NotFound('Image Not Found');
        }

        $eTag = md5($bindata);
        $timeStamp = \Mei\Utilities\Time::timeIsNonZero($fileEntity->UploadTime)
            ?
            $fileEntity->UploadTime->getTimestamp()
            :
            filemtime($this->di['utility.images']->getSavePath($savePath['filename'] . '.' . $this->di['utility.images']->mapExtension($savePath['extension'])));

        /** @var \Slim\Http\Response $response */
        $response = $response->withHeader('Content-Type', $meta['mime']);
        $response = $response->withHeader('Content-Length', $meta['size']);
        $response = $response->withHeader('Cache-Control', 'public, max-age='.(strtotime('+30 days')-time()));
        $response = $response->withHeader('ETag', $eTag);
        $response = $response->withHeader('Expires', date('r', strtotime('+30 days')));
        $response = $response->withHeader('Last-Modified', date('r', $timeStamp));

        if($request->getHeader('If-None-Match') != $eTag) { // does not match etag?
            $fh = new \GuzzleHttp\Psr7\BufferStream();
            $fh->write($bindata);
            return $response->withBody($fh);
        } else { // matches etag, return 304
            return $response->withStatus(304);
        }
    }
}
