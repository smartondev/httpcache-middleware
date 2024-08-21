<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;

class MiddlewareTestBase extends TestCase
{
    protected Psr17Factory $psr17Factory;

    protected function setUp(): void
    {
        $this->psr17Factory = new Psr17Factory();
    }

    protected function makeRequest(string $method = 'GET', string $uri = '/'): ServerRequestInterface
    {
        return $this->psr17Factory->createServerRequest($method, $uri);
    }

    protected function makeResponse(int $statusCode = 200): ResponseInterface
    {
        return $this->psr17Factory->createResponse(code: $statusCode);
    }

    protected static function createStringStream(string $content): Stream
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return new Stream($stream);
    }

    public static function getDefaultResponseHeaders(ContainerInterface $container): array
    {
        $response = $container->get(ProviderConstants::RESPONSE_INTERFACE_ID);
        /** @var ResponseInterface $response */
        return $response->getHeaders();
    }

    protected static function makeRequestHandler(ResponseInterface $response, ?callable $before = null, ?callable $after = null): RequestHandlerInterface
    {
        return new class($response, $before, $after) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response,
                                        private readonly ?\Closure         $before = null,
                                        private readonly ?\Closure         $after = null,
            )
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (is_callable($this->before)) {
                    call_user_func($this->before);
                }
                if (!is_callable($this->after)) {
                    return $this->response;
                }
                call_user_func($this->after);
                return $this->response;
            }
        };
    }
}