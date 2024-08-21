<?php

namespace SmartonDev\HttpCacheMiddleware\Http\Middleware;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;

/**
 * Middleware to handle HTTP cache headers
 */
class HttpCacheMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;
    /**
     * @var string The service id of the HttpCacheContextServiceInterface implementation in the container, must be singleton
     *
     * Override this property to use another HttpCacheContextServiceInterface implementation
     */
    protected string $containerHttpCacheContextServiceId = ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID;
    /**
     * @var string The service id of the ResponseInterface implementation in the container
     *
     * Override this property to use another ResponseInterface implementation
     */
    protected string $containerResponseId = ProviderConstants::RESPONSE_INTERFACE_ID;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $httpCacheContextService = $this->getHttpCacheContextService();
        if (!$httpCacheContextService->hasHeaders()) {
            return $response;
        }
        $isNoCache = $httpCacheContextService->isNoCache();
        if (!$isNoCache) {
            $isMethodAllowed = in_array($request->getMethod(), $httpCacheContextService->getCacheableHttpMethods());
            if (!$isMethodAllowed) {
                return $response;
            }
            $isStatusCodeAllowed = in_array($response->getStatusCode(), $httpCacheContextService->getCacheableHttpStatusCodes());
            if (!$isStatusCodeAllowed) {
                return $response;
            }
        }
        foreach ($httpCacheContextService->getHeaders() as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        return $response;
    }

    /**
     * Get the HttpCacheContextServiceInterface implementation from the container
     *
     * Override this method to use another HttpCacheContextServiceInterface implementation
     *
     * Must be a singleton
     *
     * @return HttpCacheContextServiceInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getHttpCacheContextService(): HttpCacheContextServiceInterface
    {
        return $this->container->get($this->containerHttpCacheContextServiceId);
    }

    /**
     * Create a ResponseInterface implementation with the given content
     *
     * In this level of the middleware, the content is must be a StreamInterface implementation
     *
     * Override this method to use another ResponseInterface implementation
     *
     * @param mixed $content
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function makeResponse(mixed $content): ResponseInterface
    {
        $response = $this->container->get($this->containerResponseId);
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Response must be an instance of ' . ResponseInterface::class);
        }
        if ('' === $content ?? '') {
            return $response;
        }
        if (!$content instanceof StreamInterface) {
            throw new \RuntimeException('Content must be an instance of ' . StreamInterface::class);
        }
        return $response->withBody($content);
    }
}