<?php declare(strict_types=1);

namespace SwagGraphQL\Test\Traits;

use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\HttpFoundation\Request;

trait GraphqlApiTest
{
    use IntegrationTestBehaviour;

    private function createGraphqlRequestRequest(string $query): Request
    {
        $request = Request::create(
            'localhost',
            Request::METHOD_POST,
            [],
            [],
            [],
            [],
            json_encode(['query' => $query])
        );
        $request->headers->add(['content_type' => 'application/json']);

        return $request;
    }
}