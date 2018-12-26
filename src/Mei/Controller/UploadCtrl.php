<?php

namespace Mei\Controller;

class UploadCtrl extends \Mei\Controller\BaseCtrl
{
    /**
     * @param \Slim\Http\Request $request
     * @param \Slim\Http\Response $response
     * @param $args
     * @return \Slim\Http\Response
     * @throws \Exception
     */
    public function account($request, $response, $args)
    {
        /**
         * token:
         * method: 'account'
         * ident: userId
         * tvalue: (valid until)
         **/
        $token = json_decode($this->di['utility.encryption']->decryptString($request->getParam('token')), true);
        if (!$token || $token['method'] !== 'account' || time() > $token['tvalid']) throw new \Mei\Exception\AccessDenied;

        $dataToHandle = array();

        $files = $request->getUploadedFiles();
        $url = $request->getParam('url');
        if (!$files && !$url) throw new \Mei\Exception\GeneralException('No files to upload found');

        foreach ($files as $fileArray) {
            if (!$fileArray) continue;
            foreach ($fileArray as $file) {
                if (!$file->file) continue; // empty file?
                if ($file->getSize() > $this->config['site.max_filesize']) continue;
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }
        if ($url) $dataToHandle[] = $this->di['utility.images']->getDataFromUrl($url);

        try {
            $images = $this->processUploadedData($dataToHandle, $token['ident']);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($images) {
            $qs = array('img' => $this->di['utility.encryption']->encryptUrl(implode('|', $images)));
            $urlString = '?' . http_build_query($qs);

            /** @var \Slim\Http\Response $response */
            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        } else {
            throw new \Mei\Exception\NoImages('No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?');
        }
    }

    public function screenshot($request, $response, $args)
    {
        /**
         * token:
         * method: 'screenshot'
         * ident: userId
         * tvalue: (valid until)
         **/
        $token = json_decode($this->di['utility.encryption']->decryptString($request->getParam('token')), true);
        if (!$token || $token['method'] !== 'screenshot' || time() > $token['tvalid']) throw new \Mei\Exception\AccessDenied;

        $files = $request->getUploadedFiles();
        if (!$files) throw new \Mei\Exception\GeneralException('No files to upload found');

        $imageData = array();
        foreach ($files as $fileArray) {
            if (!$fileArray) continue;
            foreach ($fileArray as $file) {
                if (!$file->file) continue;
                if ($file->getSize() > $this->config['site.max_filesize']) continue;
                $bindata = $file->getStream()->getContents();
                $metadata = $this->di['utility.images']->readImageData($bindata);

                if (!$metadata || $metadata['mime'] != 'image/png') continue;

                $imageData[] = $bindata;
            }
        }

        try {
            $images = $this->processUploadedData($imageData, $token['ident'], $args['torrentid']);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($images) {
            $qs = array(
                'action' => 'takeupload',
                'torrentid' => $args['torrentid'],
                'imgs' => $this->di['utility.encryption']->encryptUrl(implode('|', $images))
            );
            $urlString = '?' . http_build_query($qs);

            /** @var \Slim\Http\Response $response */
            return $response->withStatus(303)->withHeader('Location', "{$this->config['api.redirect']}{$urlString}");
        } else {
            throw new \Mei\Exception\NoImages('No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes or image was not PNG?');
        }
    }

    /**
     * @param \Slim\Http\Request $request
     * @param \Slim\Http\Response $response
     * @param $args
     * @return \Slim\Http\Response
     * @throws \Exception
     */
    public function api($request, $response, $args)
    {
        $auth = $request->getParam('auth');

        if (!hash_equals($auth, $this->config['api.auth_key'])) throw new \Mei\Exception\AccessDenied;

        $dataToHandle = array();

        $url = $request->getParam('url');
        $file = $request->getParam('file');
        $files = $request->getUploadedFiles();

        if ($url) {
            $dataToHandle[] = $this->di['utility.images']->getDataFromUrl($url);
        }

        if ($file) {
            $fileDecoded = base64_decode($file);
            if (sizeof($fileDecoded) <= $this->config['site.max_filesize']) $dataToHandle[] = $fileDecoded;
        }

        foreach ($files as $fileArray) {
            if (!$fileArray) continue;
            foreach ($fileArray as $file) {
                if (!$file->file) continue; // empty file?
                if ($file->getSize() > $this->config['site.max_filesize']) continue;
                $dataToHandle[] = $file->getStream()->getContents();
            }
        }

        if (!$file && !$url && !$files) throw new \Mei\Exception\GeneralException('No files to upload found');

        try {
            $images = $this->processUploadedData($dataToHandle);
        } catch (\Exception $e) {
            throw $e;
        }

        if ($images) {
            /** @var \Slim\Http\Response $response */
            $response = $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($images));
            return $response->withStatus(201);
        } else {
            throw new \Mei\Exception\NoImages('No processed images found. Possibly file was more than ' . $this->config['site.max_filesize'] . ' bytes?');
        }
    }

    private function processUploadedData(array $dataToHandle, $uploaderId = 0, $torrentId = 0)
    {
        $images = array();
        foreach ($dataToHandle as $bindata) {
            if (!$bindata) continue;
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
            } else if ($this->di['model.files_map']->getByKey($metadata['checksum_legacy'] . '.' . $metadata['extension'])) {
                $found = true;
                $isLegacy = true;
            }

            $checksum = $isLegacy ? $metadata['checksum_legacy'] : $metadata['checksum'];
            if(!$found) {
                $savePath = $this->di['utility.images']->getSavePath($checksum . '.' . $metadata['extension']);
                if (!$this->di['utility.images']->saveData($bindata, $savePath, ($uploaderId && !$torrentId && $metadata['mime'] != 'image/gif' ? true : false))) throw new \Mei\Exception\GeneralException('Unable to save file');
            }

            $filename = \Mei\Utilities\StringUtil::generateRandomString(11) . '.' . $metadata['extension'];
            $newImage = $this->di['model.files_map']->createEntity([
                'Key' => $checksum . '.' . $metadata['extension'],
                'FileName' => $filename,
                'UploaderId' => $uploaderId,
                'TorrentId' => $torrentId,
                'Protected' => 0,
                'UploadTime' => \Mei\Utilities\Time::now()
            ]);
            $this->di['model.files_map']->save($newImage);
            array_push($images, $filename);
        }
        return $images;
    }
}
