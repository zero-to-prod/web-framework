<?php

namespace Zerotoprod\WebFramework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 RequestHandler - wraps next middleware/action in pipeline.
 * @link https://github.com/zero-to-prod/web-framework
 */
class RequestHandler implements RequestHandlerInterface
{
    private $callback;

    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
