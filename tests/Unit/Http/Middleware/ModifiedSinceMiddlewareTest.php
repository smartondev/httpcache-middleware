<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use SmartonDev\HttpCache\Helpers\HttpHeaderHelper;
use SmartonDev\HttpCache\Matchers\ModifiedMatcher;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;
use SmartonDev\HttpCacheMiddleware\Http\Middleware\ModifiedSinceMiddleware;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;
use SmartonDev\HttpCacheMiddleware\Services\HttpCacheContextService;
use SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware\Mocks\LastModifiedResolverMock;

class ModifiedSinceMiddlewareTest extends MiddlewareTestBase
{
    protected function makeContainer(callable $lastModifiedCallback, HttpCacheContextServiceInterface $httpCacheContextService): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID => $httpCacheContextService,
            ProviderConstants::LAST_MODIFIED_RESOLVER_ID => fn() => new LastModifiedResolverMock($lastModifiedCallback),
            ProviderConstants::MODIFIER_MATCHER_ID => fn() => new ModifiedMatcher(),
            ProviderConstants::RESPONSE_INTERFACE_ID => fn() => $this->makeResponse(),
        ]);
        return $builder->build();
    }

    public static function dataProviderIfModifiedSince(): array
    {
        return [
            'equal' => [
                ['if-modified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-01 00:00:00'))],
                '2020-01-01 00:00:00',
                304,
            ],
            'modified' => [
                ['if-modified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-01 00:00:00'))],
                '2020-01-02 00:00:00',
                200,
            ],
            'before' => [
                ['if-modified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-02 00:00:00'))],
                '2020-01-01 00:00:00',
                304,
            ],
        ];
    }

    #[dataProvider('dataProviderIfModifiedSince')]
    public function testIfModifiedSince(array $headers, ?string $resolveDate, int $expectedStatusCode): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) use ($resolveDate) {
            return strtotime($resolveDate);
        }, $httpCacheContextService);
        $middleware = new ModifiedSinceMiddleware($container);
        $request = $this->makeRequest();
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
    }

    public static function dataProviderIfUnmodifiedSince(): array
    {
        return [
            'equal' => [
                ['if-unmodified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-01 00:00:00'))],
                '2020-01-01 00:00:00',
                200,
            ],
            'modified' => [
                ['if-unmodified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-01 00:00:00'))],
                '2020-01-02 00:00:00',
                412,
            ],
            'before' => [
                ['if-unmodified-since' => HttpHeaderHelper::toDateString(strtotime('2020-01-02 00:00:00'))],
                '2020-01-01 00:00:00',
                200,
            ],
        ];
    }

    #[DataProvider('dataProviderIfUnmodifiedSince')]
    public function testIfUnmodifiedSince(array $headers, ?string $resolveDate, int $expectedStatusCode): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer(function ($request) use ($resolveDate) {
            return strtotime($resolveDate);
        }, $httpCacheContextService);
        $middleware = new ModifiedSinceMiddleware($container);
        $request = $this->makeRequest(method: 'post');
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
    }
}