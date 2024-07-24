<?php

declare(strict_types=1);

namespace Mei\Controller;

use DI\Attribute\Inject;
use Exception;
use JsonException;
use Mei\Model\FilesMap;
use Mei\Utilities\Curl;
use Mei\Utilities\Encryption;
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
    #[Inject]
    private RouteParser $router;

    #[Inject]
    private FilesMap $filesMap;

    #[Inject]
    private Encryption $encryption;

    /**
     * @throws JsonException|HttpForbiddenException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        /* @formatter:off */
        if (!$this->encryption->hmacValid($request->getBody()->getContents(), $request->getHeaderLine('X-Hmac-Signature'))) {
            throw new HttpForbiddenException($request);
        } /* @formatter:on */

        if (
            !is_array($request->getParsedBody()) ||
            !is_array($request->getParsedBody()['images'] ?? null) ||
            empty($request->getParsedBody()['images'])
        ) {
            return $response->withStatus(400)->withJson(['success' => false, 'error' => 'No images to delete given']);
        }

        ignore_user_abort(true); // dont abort if client disconnects

        $warnings = [];
        $urls = [];
        foreach ($request->getParsedBody()['images'] as $image) {
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
                if (!$this->filesMap->getByKey($fileEntity->Key)) {
                    self::deleteImage($fileEntity->Key); // remove file from disk when it's not referenced anymore
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
                    CURLOPT_POST => true,
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HTTPHEADER => [
                        'Host: api.cloudflare.com',
                        "Authorization: Bearer {$this->config['cloudflare.api']}",
                        'Content-Type: application/json'
                    ],
                    CURLOPT_POSTFIELDS => json_encode(['files' => $urls], JSON_THROW_ON_ERROR)
                ]
            );
            if (!$result = $curl->exec()) {
                Debugger::log("Failed to clear CDN cache: {$curl->error()}", DEBUGGER::WARNING);
                return $response->withStatus(200)->withJson(['success' => true, 'warnings' => $warnings]);
            }

            try {
                $result = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                Debugger::log($e, DEBUGGER::WARNING);
                return $response->withStatus(200)->withJson(['success' => true, 'warnings' => $warnings]);
            }

            if (!@$result['success']) {
                try {
                    $err = json_encode($result['errors'], JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    Debugger::log($e, DEBUGGER::WARNING);
                    $err = '(unparsable error)';
                }
                Debugger::log("Received non-success response when clearing CDN cache: $err", DEBUGGER::WARNING);
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
