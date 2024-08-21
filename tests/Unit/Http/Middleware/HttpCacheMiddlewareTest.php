<?php

namespace SmartonDev\HttpCacheMiddleware\Tests\Unit\Http\Middleware;

use DI\ContainerBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use SmartonDev\HttpCacheMiddleware\Http\Middleware\HttpCacheMiddleware;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;
use SmartonDev\HttpCacheMiddleware\Services\HttpCacheContextService;

class HttpCacheMiddlewareTest extends MiddlewareTestBase
{
    protected function makeContainer(HttpCacheContextService $httpCacheContextService): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID => $httpCacheContextService,
            ProviderConstants::RESPONSE_INTERFACE_ID => fn() => $this->makeResponse(),
        ]);
        return $builder->build();
    }

    public function testKeepResponseObject(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $httpCacheContextService->getCacheHeaderBuilder()->reset();
        $container = $this->makeContainer($httpCacheContextService);
        $middleware = new HttpCacheMiddleware($container);
        $request = $this->makeRequest();

        $responseInput = $this->makeResponse();
        $requestHandler = $this->makeRequestHandler($responseInput);
        $responseOutput = $middleware->process($request, $requestHandler);
        $this->assertSame($responseInput, $responseOutput);
    }

    public function testNoCache(): void
    {
        $httpCacheContextService = new HttpCacheContextService();
        $container = $this->makeContainer($httpCacheContextService);
        $middleware = new HttpCacheMiddleware($container);
        $request = $this->makeRequest();

        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $response = $middleware->process($request, $requestHandler);
        $expectedHeaders = array_map(fn($value) => [$value], $httpCacheContextService->getHeaders());
        $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
            $expectedHeaders,
            $response->getHeaders(),
            array_keys($expectedHeaders),
        );
    }

    public static function dataProviderApplyByHttpStatusCode(): iterable
    {
        $allowedCodes = [
            200,
            201,
            203,
            204,
            206,
            404,
        ];
        $httpCacheContextService = new HttpCacheContextService();
        $httpCacheContextService->withCacheableHttpStatusCodes($allowedCodes);
        $httpCacheContextService->getCacheHeaderBuilder()
            ->maxAge(3600)
            ->public();

        foreach (range(100, 599) as $code) {
            yield "http $code" => [$httpCacheContextService, $code, in_array($code, $allowedCodes)];
        }
    }

    #[dataProvider('dataProviderApplyByHttpStatusCode')]
    public function testApplyByHttpStatusCode(
        HttpCacheContextService $httpCacheContextService,
        int                     $httpStatusCode,
        bool                    $expectedApply,
    ): void
    {
        $container = $this->makeContainer($httpCacheContextService);
        $middleware = new HttpCacheMiddleware($container);
        $request = $this->makeRequest();

        $requestHandler = $this->makeRequestHandler($this->makeResponse(statusCode: $httpStatusCode));
        $response = $middleware->process($request, $requestHandler);
        if ($expectedApply) {
            $expectedHeaders = array_map(fn($value) => [$value], $httpCacheContextService->getHeaders());
            $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
                $expectedHeaders,
                $response->getHeaders(),
                array_keys($expectedHeaders),
            );
            return;
        }
        $this->assertSame(static::getDefaultResponseHeaders($container), $response->getHeaders());
    }

    public static function dataProviderApplyByHttpMethod(): array
    {
        $allowedMethods = ['GET', 'HEAD'];
        $httpCacheContextService = new HttpCacheContextService();
        $httpCacheContextService->withCacheableHttpMethod($allowedMethods);
        $httpCacheContextService->getCacheHeaderBuilder()
            ->maxAge(3600)
            ->public();

        return [
            'get' => [$httpCacheContextService, 'GET', true],
            'head' => [$httpCacheContextService, 'HEAD', true],
            'post' => [$httpCacheContextService, 'POST', false],
            'put' => [$httpCacheContextService, 'PUT', false],
            'delete' => [$httpCacheContextService, 'DELETE', false],
            'patch' => [$httpCacheContextService, 'PATCH', false],
            'options' => [$httpCacheContextService, 'OPTIONS', false],
        ];
    }

    #[dataProvider('dataProviderApplyByHttpMethod')]
    public function testApplyByHttpMethod(
        HttpCacheContextService $httpCacheContextService,
        string                  $httpMethod,
        bool                    $expectedApply,
    ): void
    {
        $container = $this->makeContainer($httpCacheContextService);
        $request = $this->makeRequest(method: $httpMethod);

        $requestHandler = $this->makeRequestHandler($this->makeResponse());
        $middleware = new HttpCacheMiddleware($container);
        $response = $middleware->process($request, $requestHandler);
        if ($expectedApply) {
            $expectedHeaders = array_map(fn($value) => [$value], $httpCacheContextService->getHeaders());
            $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
                $expectedHeaders,
                $response->getHeaders(),
                array_keys($expectedHeaders),
            );
            return;
        }
        $this->assertSame(static::getDefaultResponseHeaders($container), $response->getHeaders());
    }

}