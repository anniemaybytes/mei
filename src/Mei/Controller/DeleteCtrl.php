<?php

declare(strict_types=1);

namespace Mei\Controller;

use ErrorException;
use Exception;
use JsonException;
use Mei\Model\FilesMap;
use Mei\Utilities\Curl;
use Mei\Utilities\ImageUtilities;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Routing\RouteParser;
use Tracy\Debugger;

/**
 * Class DeleteCtrl
 *
 * @package Mei\Controller
 */
final class DeleteCtrl extends BaseCtrl
{
    /** @Inject */
    private RouteParser $router;

    /** @Inject */
    private FilesMap $filesMap;

    /**
     * @throws JsonException|HttpForbiddenException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        // dont abort if client disconnects
        ignore_user_abort(true);

        $auth = $request->getParam('auth', '');
        if (!hash_equals($auth, $this->config['api.secret'])) {
            throw new HttpForbiddenException($request);
        }

        if (!$images = $request->getParam('images')) {
            return $response->withStatus(400)->withJson(['success' => false, 'error' => 'No images to delete given']);
        }
        if (!is_array($images)) {
            $images = [$images];
        }

        $warnings = [];
        $urls = [];
        foreach ($images as $image) {
            if (!is_string($image) || $image === '') {
                continue;
            }

            $fileEntity = $this->filesMap->getByFileName($image);
            if (!$fileEntity) {
                $warnings[] = "Image $image does not exist";
                continue;
            }

            if ($fileEntity->Protected) {
                $fileEntity->Protected--;
                $this->filesMap->save($fileEntity);

                $warnings[] = "Image $image is protected ($fileEntity->Protected)";
                continue;
            }

            try {
                $this->filesMap->delete($fileEntity);
            } catch (Exception $e) {
                $warnings[] = "Encountered error while processing image $image";
                Debugger::log($e, Debugger::EXCEPTION);
                continue;
            }

            try {
                // remove file from disk when it's not referenced anymore
                if (!$this->filesMap->getByKey($fileEntity->Key)) {
                    self::deleteImage($fileEntity->Key);
                }
            } catch (Exception $e) {
                Debugger::log($e, Debugger::EXCEPTION);
            }

            $urls[] = $this->router->fullUrlFor($request->getUri(), 'serve', ['image' => $image]);
        }

        if ($this->config['cloudflare.enabled']) {
            $curl = new Curl(
                "https://api.cloudflare.com/client/v4/zones/{$this->config['cloudflare.zone']}/purge_cache"
            );
            $curl->setoptArray(
                [
                    CURLOPT_ENCODING => 'UTF-8',
                    CURLOPT_USERAGENT => ImageUtilities::USER_AGENT,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER => false,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_HTTPHEADER => [
                        'Host: api.cloudflare.com',
                        "Authorization: Bearer {$this->config['cloudflare.api']}",
                        'Content-Type: application/json'
                    ],
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_POSTFIELDS => json_encode(['files' => $urls], JSON_THROW_ON_ERROR)
                ]
            );
            $result = $curl->exec();
            if ($curl->error() !== '') {
                Debugger::log(new ErrorException('Failed to clear CDN cache: ' . $curl->error()), DEBUGGER::ERROR);
                return $response->withStatus(200)->withJson(['success' => true, 'warnings' => $warnings]);
            }
            unset($curl);

            try {
                // will most likely fail if api is down as it would return html error page instead
                $result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Debugger::log(
                    new ErrorException(
                        'Failed to clear CDN cache',
                        0,
                        1,
                        __FILE__,
                        __LINE__,
                        $e
                    ),
                    DEBUGGER::ERROR
                );
            }

            if (!@$result['success']) {
                try {
                    $err = json_encode($result['errors'], JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    Debugger::log($e, DEBUGGER::WARNING);
                    $err = '(unparsable error)';
                }
                Debugger::log(new ErrorException("Failed to clear CDN cache: $err"), DEBUGGER::ERROR);
            }
        }

        return $response->withStatus(200)->withJson(['success' => true, 'warnings' => $warnings]);
    }

    /** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
    private static function deleteImage(string $filename): bool
    {
        return unlink(ImageUtilities::getSavePath($filename));
    }
}
