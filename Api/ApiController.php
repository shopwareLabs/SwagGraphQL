<?php declare(strict_types=1);

namespace SwagGraphQL\Api;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Shopware\Core\Framework\Context;
use SwagGraphQL\Resolver\QueryResolver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends Controller
{
    /** @var Schema  */
    private $schema;

    /** @var QueryResolver  */
    private $queryResolver;

    public function __construct(Schema $schema, QueryResolver $queryResolver)
    {
        $this->schema = $schema;
        $this->queryResolver = $queryResolver;
    }

    /**
     * @Route("/graphql/generate-schema", name="graphql_generate_schema", methods={"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateSchema(): Response
    {
        file_put_contents(__DIR__ . '/../Resources/schema.graphql', SchemaPrinter::doPrint($this->schema));
        return new Response();
    }

    /**
     * @Route("/graphql/query", name="graphql_query", methods={"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function query(Request $request, Context $context): Response
    {
        $body = json_decode($request->getContent(), true);

        $result = GraphQL::executeQuery(
            $this->schema,
            $body['query'],
            null,
            $context,
            $body['variables'] ?? null,
            null,
            // Default Resolver
            function ($rootValue, $args, $context, ResolveInfo $info) {
                return $this->queryResolver->resolve($rootValue, $args, $context, $info);
            }
        );

        return new JsonResponse($result->toArray());
    }
}