<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use SmartonDev\HttpCache\Builders\ETagHeaderBuilder;
use SmartonDev\HttpCache\Matchers\ETagMatcher;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;
use SmartonDev\HttpCacheMiddleware\Http\Middleware\ETagMatchMiddleware;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;
use SmartonDev\HttpCacheMiddleware\Services\HttpCacheContextService;
use SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware\Mocks\ETagResolverMock;

class EtagMatchMiddlewareTest extends MiddlewareTestBase
{
    protected function makeContainer(callable $etagCallback, HttpCacheContextServiceInterface $httpCacheContextService): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID => $httpCacheContextService,
            ProviderConstants::ETAG_RESOLVER_ID => fn() => new ETagResolverMock($etagCallback),
            ProviderConstants::ETAG_MATCHER_ID => fn() => new ETagMatcher(),
            ProviderConstants::RESPONSE_INTERFACE_ID => fn() => $this->makeResponse(),
        ]);
        return $builder->build();
    }

    public static function dataProviderResolveEmptyEtag(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'empty builder' => [new ETagHeaderBuilder()],
        ];
    }

    #[dataProvider('dataProviderResolveEmptyEtag')]
    public function testResolveEmptyEtag(null|string|ETagHeaderBuilder $etag): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) use ($etag) {
            return $etag;
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest();
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertFalse($httpCacheContextService->hasEtag());
    }

    public function testResolveTagWithRequest(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function (ServerRequestInterface $request) {
            return $request->getQueryParams()['etag'];
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest(uri: '/?etag=abc123');
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertTrue($httpCacheContextService->hasEtag());
        $this->assertSame('abc123', $httpCacheContextService->getEtag());
    }

    public static function dataProviderResolveEtag(): array
    {
        return [
            'normal' => ['"abc123"', '"abc123"'],
            'week' => ['W/"123456"', 'W/"123456"'],
            'normal with builder' => [(new ETagHeaderBuilder())->withEtag('abc567'), '"abc567"'],
            'week with builder' => [(new ETagHeaderBuilder())->withEtag('abc567')->withWeekETag(), 'W/"abc567"'],
        ];
    }

    #[dataProvider('dataProviderResolveEtag')]
    public function testResolveEtag(null|string|ETagHeaderBuilder $etag, string $expectedEtag): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) use ($etag) {
            return $etag;
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest();
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $middleware->process($request, $requestHandler);
        $this->assertTrue($httpCacheContextService->hasEtag());
        $this->assertSame($expectedEtag, $httpCacheContextService->getEtag());
    }

    public function testOverrideResolvedEtag(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) {
            return '"abc123"';
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest();
        $requestHandler = $this->makeRequestHandler($this->makeResponse(), after: function () use ($httpCacheContextService) {
            $httpCacheContextService->withEtag('"def456"');
        });
        $middleware->process($request, $requestHandler);
        $this->assertTrue($httpCacheContextService->hasEtag());
        $this->assertSame('"def456"', $httpCacheContextService->getEtag());
    }

    public function testKeepResponse(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) {
            return '"abc123"';
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest();

        $responseObject = $this->makeResponse();
        $requestHandler = $this->makeRequestHandler($responseObject);
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame($responseObject, $response);
    }

    public function testMatchIfNoneMatch(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) {
            return '"abc123"';
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest()->withHeader('if-none-match', '"abc123"');
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame(304, $response->getStatusCode());
        $this->assertTrue($httpCacheContextService->hasEtag());
        $this->assertSame('"abc123"', $httpCacheContextService->getEtag());
    }

    public function testMatchIfMatch(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) {
            return '"abc123"';
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest()->withHeader('if-match', '"abc123"');
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($httpCacheContextService->hasEtag());
    }

    public function testNotMatchIfMatch(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) {
            return '"cde456"';
        }, $httpCacheContextService);
        $middleware = new ETagMatchMiddleware($container);
        $request = $this->makeRequest()->withHeader('if-match', '"abc123"');
        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame(412, $response->getStatusCode());
        $this->assertFalse($httpCacheContextService->hasEtag());
    }

}