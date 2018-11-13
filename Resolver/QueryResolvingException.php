<?php declare(strict_types=1);

namespace SwagGraphQL\Resolver;

use GraphQL\Error\ClientAware;

class QueryResolvingException extends \Exception implements ClientAware
{

    public function isClientSafe()
    {
        return true;
    }

    public function getCategory()
    {
        return 'QueryResolving';
    }
}