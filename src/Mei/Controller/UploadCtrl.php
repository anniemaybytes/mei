<?php

declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use Mei\Exception\GeneralException;
use Mei\Exception\NoImages;
use Mei\Model\FilesMap;
use Mei\Utilities\Encryption;
use Mei\Utilities\ImageUtilities;
use Mei\Utilities\StringUtil;
use Mei\Utilities\Time;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpForbiddenException;

/**
 * Class UploadCtrl
 *
 * @package Mei\Controller
 */
final class UploadCtrl extends BaseCtrl
{
    /**
     * @Inject
     * @var ImageUtilities
     */
    private $imageUtils;

    /**
     * @Inject
     * @var FilesMap
     */
    private $filesMap;

    /**
     * @Inject
     * @var Encryption
     */
    private $encryption;

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
        $token = json_decode(
            $this->encryption->decryptString($request->getParam('token', '')),
            true
        );
        if (!$token || $token['method'] !== 'account' || time() > $token['tvalid']) {
            throw new HttpForbiddenException($request);
        }

        $dataToHandle = [];

        $uploadedFiles = $request->getUploadedFiles();
        $url = $request->getParam('url');
        if (!$uploadedFiles && !$url) {
            throw new GeneralException('No files to upload found');
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
                if (!$file->getSize() || $file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }
        if ($url) {
            $dataToHandle[] = $this->imageUtils->getDataFromUrl($url);
        }

        $images = $this->processUploadedData($dataToHandle, $token['ident']);
        if ($images) {
            $qs = [
                'imgs' => $this->encryption->encryptUrl(implode('|', $images))
            ];
            $urlString = '?' . http_build_query($qs);

            /** @var Response $response */
            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        }

        throw new NoImages(
            'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?'
        );
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     * @throws HttpForbiddenException
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
        $token = json_decode(
            $this->encryption->decryptString($request->getParam('token', '')),
            true
        );
        if (!$token || $token['method'] !== 'screenshot' || time() > $token['tvalid']) {
            throw new HttpForbiddenException($request);
        }

        $imageData = [];
        $uploadedFiles = $request->getUploadedFiles();
        if (!$uploadedFiles) {
            throw new GeneralException('No files to upload found');
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
                if (!$file->getSize() || $file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $bindata = $file->getStream()->getContents();
                $metadata = $this->imageUtils->readImageData($bindata);

                if (!$metadata || $metadata['mime'] !== 'image/png') {
                    continue;
                }
                $imageData[] = $bindata;
            }
        }

        $images = $this->processUploadedData($imageData, $token['ident'], (int)$args['torrentid']);

        if ($images) {
            $qs = [
                'action' => 'takeupload',
                'torrentid' => (int)$args['torrentid'],
                'imgs' => $this->encryption->encryptUrl(implode('|', $images))
            ];
            $urlString = '?' . http_build_query($qs);

            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        }

        throw new NoImages(
            'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes or image was not PNG?'
        );
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
            throw new HttpForbiddenException($request);
        }

        $dataToHandle = [];

        $url = $request->getParam('url');
        $file = $request->getParam('file');
        $uploadedFiles = $request->getUploadedFiles();

        if ($url) {
            $dataToHandle[] = $this->imageUtils->getDataFromUrl($url);
        }

        if ($file) {
            $fileDecoded = base64_decode($file);
            if (strlen($fileDecoded) <= $this->config['site.max_filesize']) {
                $dataToHandle[] = $fileDecoded;
            }
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
                if (!$file->getSize() || $file->getSize() > $this->config['site.max_filesize']) {
                    continue;
                }
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }

        if (!$file && !$url && !$uploadedFiles) {
            throw new GeneralException('No files to upload found');
        }

        $images = $this->processUploadedData($dataToHandle);
        if ($images) {
            return $response->withStatus(201)->withJson(
                ['success' => true, 'images' => $images]
            );
        }

        throw new NoImages(
            'No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?'
        );
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
            $metadata = $this->imageUtils->readImageData($bindata);

            // invalid data, or not allowed format
            if (!$metadata) {
                $images[] = 'error.jpg';
                continue;
            }

            $found = $isLegacy = false;
            if ($this->filesMap->getByKey($metadata['checksum'] . '.' . $metadata['extension'])) {
                $found = true;
                $isLegacy = false;
            } elseif ($this->filesMap->getByKey($metadata['checksum_legacy'] . '.' . $metadata['extension'])) {
                $found = true;
                $isLegacy = true;
            }

            $checksum = $isLegacy ? $metadata['checksum_legacy'] : $metadata['checksum'];
            if (!$found) {
                $savePath = $this->imageUtils->getSavePath($checksum . '.' . $metadata['extension']);
                if (!$this->imageUtils->saveData($bindata, $savePath, false)) {
                    throw new GeneralException('Unable to save file');
                }
            }

            $filename = StringUtil::generateRandomString(11) . '.' . $metadata['extension'];
            $newImage = $this->filesMap->createEntity(
                [
                    'Key' => $checksum . '.' . $metadata['extension'],
                    'FileName' => $filename,
                    'UploaderId' => $uploaderId,
                    'TorrentId' => $torrentId,
                    'Protected' => 0,
                    'UploadTime' => Time::now()
                ]
            );
            $this->filesMap->save($newImage);
            $images[] = $filename;
        }
        return $images;
    }
}
