<?php

namespace SmartonDev\HttpCacheMiddleware\Http\Middleware;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SmartonDev\HttpCache\ETagHeaderBuilder;
use SmartonDev\HttpCache\ETagMatcher;
use SmartonDev\HttpCacheMiddleware\Contracts\ETagResolverInterface;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;
use SmartonDev\HttpCacheMiddleware\Http\Constants\HttpStatusCodeConstants;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;

/**
 * Middleware to handle ETag matching
 */
class ETagMatchMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;
    /**
     * @var string The service id of the HttpCacheContextServiceInterface implementation in the container, must be singleton
     *
     * Override this property to use another HttpCacheContextServiceInterface implementation
     */
    protected string $containerHttpCacheContextServiceId = ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID;
    /**
     * @var string The service id of the ETagMatcher implementation in the container
     *
     * Override this property to use another ETagMatcher implementation
     */
    protected string $containerETagMatcherId = ProviderConstants::ETAG_MATCHER_ID;
    /**
     * @var string The service id of the ETagResolverInterface implementation in the container
     *
     * Override this property to use another ETagResolverInterface implementation
     */
    protected string $containerETagResolverId = ProviderConstants::ETAG_RESOLVER_ID;
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
        $etag = $this->resolveEtag($request);
        if (null === $etag || trim($etag) === '') {
            return $handler->handle($request);
        }
        $etagMatcher = $this->container->get($this->containerETagMatcherId);
        if (!$etagMatcher instanceof ETagMatcher) {
            throw new \RuntimeException('ETagMatcher must be an instance of ETagMatcher');
        }
        $etagMatcher->headers($request->getHeaders());
        $etagMatches = $etagMatcher->matches($etag);
        $httpCacheContextService = $this->container->get($this->containerHttpCacheContextServiceId);
        if (!$httpCacheContextService instanceof HttpCacheContextServiceInterface) {
            throw new \RuntimeException('HttpCacheContextService must be an instance of ' . HttpCacheContextServiceInterface::class);
        }
        /** @var HttpCacheContextServiceInterface $httpCacheContextService */
        if ($etagMatches->matchesIfNoneMatchHeader()) {
            $httpCacheContextService->withEtag($etag);
            return $this->makeResponse(HttpStatusCodeConstants::NOT_MODIFIED);
        }
        if ($etagMatches->matchesIfMatchHeader()) {
            return $handler->handle($request);
        }
        if ($etagMatcher->hasIfMatchHeader()) {
            return $this->makeResponse(HttpStatusCodeConstants::PRECONDITION_FAILED);
        }
        $response = $handler->handle($request);
        if (!$httpCacheContextService->hasEtag()) {
            $httpCacheContextService->withEtag($etag);
        }
        return $response;
    }

    /**
     * Resolve the ETag for the given request
     *
     * Override this method to resolve the ETag from the request by another way
     *
     * @param ServerRequestInterface $request
     * @return string|ETagHeaderBuilder|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolveEtag(ServerRequestInterface $request): null|string|ETagHeaderBuilder
    {
        $etagResolver = $this->container->get($this->containerETagResolverId);
        if (!$etagResolver instanceof ETagResolverInterface) {
            throw new \RuntimeException('ETagResolver must be an instance of ' . ETagResolverInterface::class);
        }

        return $etagResolver->resolveETag($request);
    }

    /**
     * Make a ResponseInterface implementation with the given status code
     *
     * If you want to return specific content, you can override this method
     *
     * @param int $statusCode
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function makeResponse(int $statusCode): ResponseInterface
    {
        $response = $this->container->get($this->containerResponseId);
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Response must be an instance of ' . ResponseInterface::class);
        }
        return $response->withStatus($statusCode);
    }

}