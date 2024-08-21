<?php

namespace SmartonDev\HttpCacheMiddleware\Providers\Constants;

use Psr\Http\Message\ResponseInterface;

class ProviderConstants
{
    /**
     * Service ID for the HttpCacheContextService
     *
     * This service must be a singleton
     */
    public const HTTP_CACHE_CONTEXT_SERVICE_ID = 'smartondev.HttpCacheContextService';
    /**
     * Service ID for the ETagMatcher
     */
    public const ETAG_MATCHER_ID = 'smartondev.ETagMatcher';
    /**
     * Service ID for the ModifiedMatcher
     */
    public const MODIFIER_MATCHER_ID = 'smartondev.ModifiedMatcher';
    /**
     * Service ID for the ResponseInterface
     */
    public const RESPONSE_INTERFACE_ID = ResponseInterface::class;
    /**
     * Service ID for the ETagResolver
     */
    public const ETAG_RESOLVER_ID = 'smartondev.ETagResolver';
    /**
     * Service ID for the LastModifiedResolver
     */
    public const LAST_MODIFIED_RESOLVER_ID = 'smartondev.LastModifiedResolver';
}