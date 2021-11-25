<?php

declare(strict_types=1);

namespace Solventt\Csrf\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response;
use Solventt\Csrf\CsrfMiddleware;
use Solventt\Csrf\Interfaces\CsrfTokenInterface;
use Solventt\Csrf\MaskedCsrfToken;
use Solventt\Csrf\SecurityHelper;
use Solventt\Csrf\SessionTokenStorage;

class CsrfMiddlewareTest extends TestCase
{
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;
    private CsrfMiddleware $middleware;
    private CsrfTokenInterface $csrfToken;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);

        $this->csrfToken = $this->createMock(CsrfTokenInterface::class);
        $this->csrfToken->method('getName')->willReturn(CsrfTokenInterface::DEFAULT_NAME);

        $this->middleware = new CsrfMiddleware($this->csrfToken, new ResponseFactory());
    }

    public function testGetRequest()
    {
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->expects(self::never())->method('getParsedBody');

        $this->middleware->process($this->request, $this->handler);
    }

    public function testSuccessfulPostRequestWithOriginalToken()
    {
        $csrfToken = new MaskedCsrfToken(new SessionTokenStorage(), new SecurityHelper());

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn([$csrfToken->getName() => $csrfToken->getValue()]);

        $this->handler->expects(self::once())->method('handle')->with($this->request);

        $this->middleware = new CsrfMiddleware($csrfToken, new ResponseFactory());

        $this->middleware->process($this->request, $this->handler);
    }

    public function testEmptyRequestToken()
    {
        $this->csrfToken->expects(self::never())->method('equals');

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeader')->willReturn([]);

        $this->handler->expects(self::never())->method('handle')->with($this->request);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Bad Request', (string) $response->getBody());
    }

    public function testArrayInsteadOfStringFromRequestData()
    {
        $this->csrfToken->expects(self::never())->method('equals');

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn([CsrfTokenInterface::DEFAULT_NAME => ['someValue']]);

        $this->handler->expects(self::never())->method('handle')->with($this->request);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Bad Request', (string) $response->getBody());
    }

    public function testMismatchedTokens()
    {
        $this->csrfToken->expects(self::once())->method('equals')->willReturn(false);

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')
            ->willReturn([CsrfTokenInterface::DEFAULT_NAME => 'someValue']);

        $this->handler->expects(self::never())->method('handle')->with($this->request);

        $response = $this->middleware->process($this->request, $this->handler);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Bad Request', (string) $response->getBody());
    }

    public function testSuccessWithAjaxRequest()
    {
        $this->csrfToken->expects(self::once())->method('equals')->willReturn(true);

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->expects(self::once())->method('getHeader')->willReturn(['someValue']);

        $this->handler->expects(self::once())->method('handle')->with($this->request);

        $this->middleware->process($this->request, $this->handler);
    }

    public function testCustomFailureHandler()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getHeader')->willReturn([]);

        $failureHandler = function (): ResponseInterface {
            $response = new Response(403);
            $response->getBody()->write('Forbidden');
            return $response;
        };

        $middleware = new CsrfMiddleware($this->csrfToken, new ResponseFactory(), $failureHandler);

        $response = $middleware->process($this->request, $this->handler);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Forbidden', (string) $response->getBody());
    }

    public function testCustomHeaderName()
    {
        $this->middleware->setHeaderName('X-CUSTOM-HEADER');

        self::assertInaccessiblePropertySame(
            'X-CUSTOM-HEADER',
            $this->middleware,
            'headerName'
        );
    }
}