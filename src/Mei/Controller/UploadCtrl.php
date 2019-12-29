<?php declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use Mei\Exception\AccessDenied;
use Mei\Exception\GeneralException;
use Mei\Exception\NoImages;
use Mei\Utilities\StringUtil;
use Mei\Utilities\Time;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class UploadCtrl
 *
 * @package Mei\Controller
 */
class UploadCtrl extends BaseCtrl
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws Exception
     */
    public function account(Request $request, Response $response, array $args): Response
    {
        /**
         * token:
         * method: 'account'
         * ident: userId
         * tvalue: (valid until)
         **/
        $token = json_decode($this->di['utility.encryption']->decryptString($request->getParam('token')), true);
        if (!$token || $token['method'] !== 'account' || time() > $token['tvalid']) {
            throw new AccessDenied();
        }

        $dataToHandle = [];

        $files = $request->getUploadedFiles();
        $url = $request->getParam('url');
        if (!$files && !$url) {
            throw new GeneralException('No files to upload found');
        }

        foreach ($files as $fileArray) {
            if (!$fileArray) {
                continue;
            }
            foreach ($fileArray as $file) {
                if (!$file->file) {
                    continue;
                } // empty file?
                if ($file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }
        if ($url) {
            $dataToHandle[] = $this->di['utility.images']->getDataFromUrl($url);
        }

        try {
            $images = $this->processUploadedData($dataToHandle, $token['ident']);
        } catch (Exception $e) {
            throw $e;
        }

        if ($images) {
            $qs = ['img' => $this->di['utility.encryption']->encryptUrl(implode('|', $images))];
            $urlString = '?' . http_build_query($qs);

            /** @var Response $response */
            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        } else {
            throw new NoImages(
                'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?'
            );
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws AccessDenied
     * @throws GeneralException
     * @throws NoImages
     * @throws Exception
     */
    public function screenshot(Request $request, Response $response, array $args): Response
    {
        /**
         * token:
         * method: 'screenshot'
         * ident: userId
         * tvalue: (valid until)
         **/
        $token = json_decode($this->di['utility.encryption']->decryptString($request->getParam('token')), true);
        if (!$token || $token['method'] !== 'screenshot' || time() > $token['tvalid']) {
            throw new AccessDenied();
        }

        $files = $request->getUploadedFiles();
        if (!$files) {
            throw new GeneralException('No files to upload found');
        }

        $imageData = [];
        foreach ($files as $fileArray) {
            if (!$fileArray) {
                continue;
            }
            foreach ($fileArray as $file) {
                if (!$file->file) {
                    continue;
                }
                if ($file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $bindata = $file->getStream()->getContents();
                $metadata = $this->di['utility.images']->readImageData($bindata);

                if (!$metadata || $metadata['mime'] != 'image/png') {
                    continue;
                }

                $imageData[] = $bindata;
            }
        }

        try {
            $images = $this->processUploadedData($imageData, $token['ident'], (int)$args['torrentid']);
        } catch (Exception $e) {
            throw $e;
        }

        if ($images) {
            $qs = [
                'action' => 'takeupload',
                'torrentid' => (int)$args['torrentid'],
                'imgs' => $this->di['utility.encryption']->encryptUrl(implode('|', $images))
            ];
            $urlString = '?' . http_build_query($qs);

            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        } else {
            throw new NoImages(
                'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes or image was not PNG?'
            );
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws Exception
     */
    public function api(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getParam('auth');

        if (!hash_equals($auth, $this->config['api.auth_key'])) {
            throw new AccessDenied();
        }

        $dataToHandle = [];

        $url = $request->getParam('url');
        $file = $request->getParam('file');
        $files = $request->getUploadedFiles();

        if ($url) {
            $dataToHandle[] = $this->di['utility.images']->getDataFromUrl($url);
        }

        if ($file) {
            $fileDecoded = base64_decode($file);
            if (strlen($fileDecoded) <= $this->config['site.max_filesize']) {
                $dataToHandle[] = $fileDecoded;
            }
        }

        foreach ($files as $fileArray) {
            if (!$fileArray) {
                continue;
            }
            foreach ($fileArray as $file) {
                if (!$file->file) {
                    continue;
                } // empty file?
                if ($file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }

        if (!$file && !$url && !$files) {
            throw new GeneralException('No files to upload found');
        }

        try {
            $images = $this->processUploadedData($dataToHandle);
        } catch (Exception $e) {
            throw $e;
        }

        if ($images) {
            /** @var Response $response */
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($images));
            return $response->withStatus(201);
        } else {
            throw new NoImages(
                'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?'
            );
        }
    }

    /**
     * @param array $dataToHandle
     * @param int $uploaderId
     * @param int $torrentId
     *
     * @return array
     * @throws GeneralException
     * @throws Exception
     */
    private function processUploadedData(array $dataToHandle, int $uploaderId = 0, int $torrentId = 0): array
    {
        $images = [];
        foreach ($dataToHandle as $bindata) {
            if (!$bindata) {
                continue;
            }
            $metadata = $this->di['utility.images']->readImageData($bindata);

            // invalid data, or not allowed format
            if (!$metadata) {
                array_push($images, 'error.jpg');
                continue;
            }

            $found = $isLegacy = false;
            if ($this->di['model.files_map']->getByKey($metadata['checksum'] . '.' . $metadata['extension'])) {
                $found = true;
                $isLegacy = false;
            } elseif ($this->di['model.files_map']->getByKey(
                $metadata['checksum_legacy'] . '.' . $metadata['extension']
            )) {
                $found = true;
                $isLegacy = true;
            }

            $checksum = $isLegacy ? $metadata['checksum_legacy'] : $metadata['checksum'];
            if (!$found) {
                $savePath = $this->di['utility.images']->getSavePath($checksum . '.' . $metadata['extension']);
                if (!$this->di['utility.images']->saveData($bindata, $savePath, false)) {
                    throw new GeneralException('Unable to save file');
                }
            }

            $filename = StringUtil::generateRandomString(11) . '.' . $metadata['extension'];
            $newImage = $this->di['model.files_map']->createEntity(
                [
                    'Key' => $checksum . '.' . $metadata['extension'],
                    'FileName' => $filename,
                    'UploaderId' => $uploaderId,
                    'TorrentId' => $torrentId,
                    'Protected' => 0,
                    'UploadTime' => Time::now()
                ]
            );
            $this->di['model.files_map']->save($newImage);
            array_push($images, $filename);
        }
        return $images;
    }
}
