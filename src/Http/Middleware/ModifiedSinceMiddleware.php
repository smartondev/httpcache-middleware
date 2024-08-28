<?php

namespace SmartonDev\HttpCacheMiddleware\Http\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SmartonDev\HttpCache\Matchers\ModifiedMatcher;
use SmartonDev\HttpCacheMiddleware\Contracts\HttpCacheContextServiceInterface;
use SmartonDev\HttpCacheMiddleware\Contracts\LastModifiedResolverInterface;
use SmartonDev\HttpCacheMiddleware\Http\Constants\HttpStatusCodeConstants;
use SmartonDev\HttpCacheMiddleware\Providers\Constants\ProviderConstants;

/**
 * Middleware to handle If-Modified-Since and If-Unmodified-Since headers
 */
class ModifiedSinceMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;
    /**
     * @var string The service id of the ModifiedMatcher implementation in the container
     *
     * Override this property to use another ModifiedMatcher implementation
     */
    protected string $containerModifierMatcherId = ProviderConstants::MODIFIER_MATCHER_ID;
    /**
     * @var string The service id of the ResponseInterface implementation in the container
     *
     * Override this property to use another ResponseInterface implementation
     */
    protected string $containerResponseId = ProviderConstants::RESPONSE_INTERFACE_ID;
    /**
     * @var string The service id of the LastModifiedResolverInterface implementation in the container
     *
     * Override this property to use another LastModifiedResolverInterface implementation
     */
    protected string $containerLastModifiedResolverId = ProviderConstants::LAST_MODIFIED_RESOLVER_ID;
    /**
     * @var string The service id of the HttpCacheContextServiceInterface implementation in the container, must be singleton
     *
     * Override this property to use another HttpCacheContextServiceInterface implementation
     */
    protected string $containerHttpCacheContextServiceId = ProviderConstants::HTTP_CACHE_CONTEXT_SERVICE_ID;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $lastModified = $this->resolveLastModified($request);
        if (null === $lastModified) {
            return $handler->handle($request);
        }

        $modifiedMatcher = $this->container->make($this->containerModifierMatcherId);
        if (!$modifiedMatcher instanceof ModifiedMatcher) {
            throw new \RuntimeException('ModifiedMatcher must be an instance of ModifiedMatcher');
        }
        /** @var ModifiedMatcher $modifiedMatcher */
        $modifiedMatcher->headers($request->getHeaders());
        $modifiedMatches = $modifiedMatcher
            ->matches($lastModified);
        if (!$modifiedMatcher->hasIfModifiedSinceHeader() && !$modifiedMatcher->hasIfUnmodifiedSinceHeader()) {
            return $handler->handle($request);
        }
        $httpCacheContextService = $this->container->get($this->containerHttpCacheContextServiceId);
        if (!$httpCacheContextService instanceof HttpCacheContextServiceInterface) {
            throw new \RuntimeException('HttpCacheContextService must be an instance of ' . HttpCacheContextServiceInterface::class);
        }
        if ($modifiedMatcher->hasIfModifiedSinceHeader()) {
            if ($modifiedMatches->isModifiedSince()) {
                $response = $handler->handle($request);
                if (!$httpCacheContextService->hasLatModified()) {
                    $httpCacheContextService->withLastModified($lastModified);
                }
                return $response;
            }
            $httpCacheContextService->withLastModified($lastModified);
            return $this->makeResponse(statusCode: HttpStatusCodeConstants::NOT_MODIFIED);
        }

        if ($modifiedMatches->isUnmodifiedSince()) {
            $response = $handler->handle($request);
        } else {
            $response = $this->makeResponse(statusCode: HttpStatusCodeConstants::PRECONDITION_FAILED);
        }

        if (!$httpCacheContextService->hasLatModified()) {
            $httpCacheContextService->withLastModified($lastModified);
        }

        return $response;
    }

    /**
     * Resolve the last modified time for the given request
     *
     * Override this method to resolve the last modified time from the request by another way
     *
     * @param ServerRequestInterface $request
     * @return int|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function resolveLastModified(ServerRequestInterface $request): ?int
    {
        $callback = $this->container->make($this->containerLastModifiedResolverId);
        if (!$callback instanceof LastModifiedResolverInterface) {
            throw new \InvalidArgumentException('Callback must implement ' . LastModifiedResolverInterface::class);
        }
        return $callback->resolveLastModifiedTime($request);
    }

    /**
     * Create a ResponseInterface implementation with the given content and status code
     *
     * Override this method to use another ResponseInterface implementation
     *
     * @param mixed $content
     * @param int $statusCode
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function makeResponse(mixed $content = '', int $statusCode = 200): ResponseInterface
    {
        $response = $this->container->get($this->containerResponseId);
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Response must be an instance of ' . ResponseInterface::class);
        }
        $response = $response->withStatus($statusCode);
        if ('' === $content ?? '') {
            return $response;
        }
        if (!$content instanceof StreamInterface) {
            throw new \RuntimeException("Not supported content");
        }
        return $response->withBody($content);
    }
}