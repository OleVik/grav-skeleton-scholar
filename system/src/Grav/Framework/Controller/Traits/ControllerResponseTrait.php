<?php

/**
 * @package    Grav\Framework\Controller
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

declare(strict_types=1);

namespace Grav\Framework\Controller\Traits;

use Grav\Common\Config\Config;
use Grav\Common\Debugger;
use Grav\Common\Grav;
use Grav\Framework\Psr7\Response;
use Grav\Framework\RequestHandler\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ControllerResponseTrait
{
    /**
     * Display the current page.
     *
     * @return Response
     */
    protected function createDisplayResponse(): ResponseInterface
    {
        return new Response(418);
    }

    /**
     * @param string $content
     * @param int|null $code
     * @param array|null $headers
     * @return Response
     */
    protected function createHtmlResponse(string $content, int $code = null, array $headers = null): ResponseInterface
    {
        $code = $code ?? 200;
        if ($code < 100 || $code > 599) {
            $code = 500;
        }
        $headers = $headers ?? [];

        return new Response($code, $headers, $content);
    }

    /**
     * @param array $content
     * @param int|null $code
     * @param array|null $headers
     * @return Response
     */
    protected function createJsonResponse(array $content, int $code = null, array $headers = null): ResponseInterface
    {
        $code = $code ?? $content['code'] ?? 200;
        if (null === $code || $code < 100 || $code > 599) {
            $code = 200;
        }
        $headers = ($headers ?? []) + [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate'
        ];

        return new Response($code, $headers, json_encode($content));
    }

    /**
     * @param string $url
     * @param int|null $code
     * @return Response
     */
    protected function createRedirectResponse(string $url, int $code = null): ResponseInterface
    {
        if (null === $code || $code < 301 || $code > 307) {
            $code = $this->getConfig()->get('system.pages.redirect_default_code', 302);
        }

        $accept = $this->getAccept(['application/json', 'text/html']);

        if ($accept === 'application/json') {
            return $this->createJsonResponse(['code' => $code, 'status' => 'redirect', 'redirect' => $url]);
        }

        return new Response($code, ['Location' => $url]);
    }

    /**
     * @param \Throwable $e
     * @return ResponseInterface
     */
    protected function createErrorResponse(\Throwable $e): ResponseInterface
    {
        $validCodes = [
            400, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418,
            422, 423, 424, 425, 426, 428, 429, 431, 451, 500, 501, 502, 503, 504, 505, 506, 507, 508, 511
        ];

        if ($e instanceof RequestException) {
            $code = $e->getHttpCode();
            $reason = $e->getHttpReason();
        } else {
            $code = $e->getCode();
            $reason = null;
        }

        if (!in_array($code, $validCodes, true)) {
            $code = 500;
        }

        $message = $e->getMessage();
        $response = [
            'code' => $code,
            'status' => 'error',
            'message' => $message,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        /** @var Debugger $debugger */
        $debugger = Grav::instance()['debugger'];
        if ($debugger->enabled()) {
            $response['error'] += [
                'type' => \get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ];
        }

        $accept = $this->getAccept(['application/json', 'text/html']);

        if ($accept === 'text/html') {
            $request = $this->getRequest();
            $method = $request->getMethod();

            // On POST etc, redirect back to the previous page.
            if ($method !== 'GET' && $method !== 'HEAD') {
                $this->setMessage($message, 'error');
                $referer = $request->getHeaderLine('Referer');
                return $this->createRedirectResponse($referer, 303);
            }

            // TODO: improve error page
            return $this->createHtmlResponse($response['message'], $code);
        }

        return new Response($code, ['Content-Type' => 'application/json'], json_encode($response), '1.1', $reason);
    }

    protected function getAccept(array $compare)
    {
        $accepted = [];
        foreach ($this->getRequest()->getHeader('Accept') as $accept) {
            foreach (explode(',', $accept) as $item) {
                if (!$item) {
                    continue;
                }

                $split = explode(';q=', $item);
                $mime = array_shift($split);
                $priority = array_shift($split) ?? 1.0;

                $accepted[$mime] = $priority;
            }
        }

        arsort($accepted);

        // TODO: add support for image/* etc
        $list = array_intersect($compare, array_keys($accepted));
        if (!$list && (isset($accepted['*/*']) || isset($accepted['*']))) {
            return reset($compare);
        }

        return reset($list);
    }

    /**
     * @return ServerRequestInterface
     */
    abstract protected function getRequest(): ServerRequestInterface;

    /**
     * @param string $message
     * @param string $type
     * @return $this
     */
    abstract protected function setMessage($message, $type = 'info');

    /**
     * @return Config
     */
    abstract protected function getConfig(): Config;
}
