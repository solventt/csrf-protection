<?php

declare(strict_types=1);

namespace Solventt\Csrf;

use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Solventt\Csrf\Interfaces\CsrfTokenInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    private string $headerName = 'X-CSRF-Token';

    public function __construct(
        private CsrfTokenInterface $token,
        private ResponseFactoryInterface $responseFactory,
        private ?Closure $failureHandler = null
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $data = $request->getParsedBody();

            /** @var string|null|array $requestToken */
            $requestToken = $data[$this->token->getName()] ?? null;

            // check ajax request
            if (!$requestToken) {
                $values = $request->getHeader($this->headerName);
                $requestToken = reset($values);
            }

            if (empty($requestToken) || is_array($requestToken) || !$this->token->equals($requestToken)) {
                return $this->handleFailure();
            }
        }

        return $handler->handle($request);
    }

    private function handleFailure(): ResponseInterface
    {
        $handler = $this->failureHandler;

        if ($handler === null) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('Bad Request');
            return $response;
        }

        /** @var Closure():ResponseInterface $handler */
        return $handler();
    }

    public function setHeaderName(string $name): void
    {
        $this->headerName = $name;
    }
}