<?php declare(strict_types=1);

namespace SwagGraphQL\Api;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class UnsupportedContentTypeException extends ShopwareHttpException
{
    public function __construct(string $unsupportedType, string ... $supportedTypes)
    {
        parent::__construct(sprintf(
            'Unsupported Content-Type, got "%s", supported are "%s"',
            $unsupportedType,
            implode(', "', $supportedTypes)
        ));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
    }

    public function getErrorCode(): string
    {
        return 'GraphQl__UnsupportedContentType';
    }
}