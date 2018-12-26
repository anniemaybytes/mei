<?php

namespace Mei\Controller;

class DeleteCtrl extends \Mei\Controller\BaseCtrl
{
    /**
     * @param \Slim\Http\Request $request
     * @param \Slim\Http\Response $response
     * @param $args
     * @return \Slim\Http\Response
     */
    public function delete($request, $response, $args)
    {
        $auth = $request->getParam('auth');

        if(!hash_equals($auth, $this->config['api.auth_key'])) {
            return $response->withJson(array('success' => false, 'reason' => 'access denied'))->withStatus(403);
        }

        $info = pathinfo($args['img']);
        $fileEntity = $this->di['model.files_map']->getByFileName($info['filename'] . '.' . $info['extension']);

        if (!$fileEntity) {
            return $response->withJson(array('success' => false, 'reason' => 'nothing to remove'))->withStatus(200);
        }
        if ($fileEntity->Protected) {
            $fileEntity->Protected--;
            $this->di['model.files_map']->save($fileEntity); // decrease protected count by one
            return $response->withJson(array('success' => false, 'reason' => 'protected image (' . $fileEntity->Protected . ')'))->withStatus(200);
        }

        $uri = $request->getUri();
        $domain = $uri->getScheme() . '://' . $uri->getHost();
        $urls = [
            ($domain . $this->di['router']->pathFor('serve', ['img' => $info['filename'] . '.' . $info['extension']])),
            ($domain . $this->di['router']->pathFor('serve:legacy', ['img' => $info['filename'] . '.' . $info['extension']]))
        ];

        foreach(\Mei\Controller\ServeCtrl::$legacySizes as $resInfo) // handling common resolutions + crop
        {
            $urls[] = ($domain . $this->di['router']->pathFor('serve', [
                    'img' => (
                        $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '.' . $info['extension']
                    )
                ]));
            $urls[] = ($domain . $this->di['router']->pathFor('serve:legacy', [
                    'img' => (
                        $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '.' . $info['extension']
                    )
                ]));

            $urls[] = ($domain . $this->di['router']->pathFor('serve', [
                    'img' => (
                        $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '-crop.' . $info['extension']
                    )
                ]));
            $urls[] = ($domain . $this->di['router']->pathFor('serve:legacy', [
                    'img' => (
                        $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '-crop.' . $info['extension']
                    )
                ]));
        }

        try{
            $this->di['model.files_map']->delete($fileEntity);
            if(!$this->di['model.files_map']->getByKey($fileEntity->Key)) { // file does not exist anymore anywhere, remove it
                $savePath = pathinfo($fileEntity->Key);
                $this->di['utility.images']->deleteImage($this->di['utility.images']->getSavePath($savePath['filename'] . '.' . $this->di['utility.images']->mapExtension($savePath['extension'])));
            }
        } catch(\Exception $e) {
            return $response->withJson(array('success' => false, 'reason' => 'unknown failure'))->withStatus(200);
        }

        try{
            $this->di['utility.images']->clearCacheForImage($urls);
        } catch(\Exception $e) {
            error_log("Caught $e");
        }

        return $response->withJson(array('success' => true))->withStatus(200);
    }
}
