<?php declare(strict_types=1);

namespace Mei\Controller;

use Exception;
use Mei\Model\FilesMap;
use Mei\Utilities\ImageUtilities;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteParser;
use Tracy\Debugger;

/**
 * Class DeleteCtrl
 *
 * @package Mei\Controller
 */
class DeleteCtrl extends BaseCtrl
{
    /**
     * @Inject
     * @var RouteParser
     */
    private $router;

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
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return Response
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // dont abort if client disconnects
        ignore_user_abort(true);

        $auth = $request->getParam('auth');

        if (!hash_equals($auth, $this->config['api.auth_key'])) {
            return $response->withStatus(403)->withJson(['success' => false, 'reason' => 'access denied']);
        }

        $imgs = json_decode($request->getParam('imgs'));
        $success = count($imgs);

        if (!$success) {
            return $response->withStatus(200)->withJson(['success' => false, 'reason' => 'imgs array was empty']);
        }

        foreach ($imgs as $img) {
            $info = pathinfo($img);
            $fileEntity = $this->filesMap->getByFileName(
                $info['filename'] . '.' . $info['extension']
            );

            if (!$fileEntity) {
                $success--;
                continue;
            }
            if ($fileEntity->Protected) {
                $fileEntity->Protected--;
                $this->filesMap->save($fileEntity); // decrease protected count by one

                $success--;
                continue;
            }

            $uri = $request->getUri();
            $domain = $uri->getScheme() . '://' . $uri->getHost();
            $urls = [
                ($domain . $this->router->relativeUrlFor(
                        'serve',
                        ['img' => $info['filename'] . '.' . $info['extension']]
                    )),
                ($domain . $this->router->relativeUrlFor(
                        'serve:legacy',
                        ['img' => $info['filename'] . '.' . $info['extension']]
                    ))
            ];

            foreach (ServeCtrl::$legacySizes as $resInfo) // handling common resolutions + crop
            {
                $urls[] = ($domain . $this->router->relativeUrlFor(
                        'serve',
                        [
                            'img' => (
                                $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '.' . $info['extension']
                            )
                        ]
                    ));
                $urls[] = ($domain . $this->router->relativeUrlFor(
                        'serve:legacy',
                        [
                            'img' => (
                                $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '.' . $info['extension']
                            )
                        ]
                    ));

                $urls[] = ($domain . $this->router->relativeUrlFor(
                        'serve',
                        [
                            'img' => (
                                $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '-crop.' . $info['extension']
                            )
                        ]
                    ));
                $urls[] = ($domain . $this->router->relativeUrlFor(
                        'serve:legacy',
                        [
                            'img' => (
                                $info['filename'] . '-' . $resInfo[0] . 'x' . $resInfo[1] . '-crop.' . $info['extension']
                            )
                        ]
                    ));
            }

            try {
                $this->filesMap->delete($fileEntity);
                if (!$this->filesMap->getByKey(
                    $fileEntity->Key
                )) { // file does not exist anymore anywhere, remove it
                    $savePath = pathinfo($fileEntity->Key);
                    $this->imageUtils->deleteImage(
                        $this->imageUtils->getSavePath(
                            $savePath['filename'] . '.' . $this->imageUtils->mapExtension(
                                $savePath['extension']
                            )
                        )
                    );
                }
            } catch (Exception $e) {
                $success--;
                Debugger::log($e, Debugger::EXCEPTION);
            }

            try {
                $this->imageUtils->clearCacheForImage($urls);
            } catch (Exception $e) {
                Debugger::log($e, Debugger::EXCEPTION);
            }
        }

        return $response->withStatus(200)->withJson(['success' => $success]);
    }
}
