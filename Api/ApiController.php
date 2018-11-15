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
     * GraphQL Endpoint
     *
     * supports: @see https://graphql.github.io/learn/serving-over-http/#http-methods-headers-and-body
     * GET: query as query string
     * POST with JSON: query in body like {'query': '...'}
     * POST with application/graphql: query is complete body
     * 
     * @Route("/graphql", name="graphql", methods={"GET|POST"})
     *
     * @param Request $request
     * @param Context $context
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws UnsupportedContentTypeException
     */
    public function query(Request $request, Context $context): Response
    {
        $query = null;
        $variables = null;
        if ($request->getMethod() === Request::METHOD_POST) {
            if ($request->headers->get('content_type') === 'application/json') {
                $body = json_decode($request->getContent(), true);
                $query = $body['query'];
                $variables = $body['variables'] ?? null;
            } else if ($request->headers->get('content_type') === 'application/graphql') {
                $query = $request->getContent();
            } else {
                throw new UnsupportedContentTypeException(
                    $request->headers->get('content_type'),
                    'application/json',
                    'application/graphql'
                );
            }
        } else {
            $query = $request->query->get('query');
        }

        $result = GraphQL::executeQuery(
            $this->schema,
            $query,
            null,
            $context,
            $variables,
            null,
            // Default Resolver
            function ($rootValue, $args, $context, ResolveInfo $info) {
                return $this->queryResolver->resolve($rootValue, $args, $context, $info);
            }
        );

        return new JsonResponse($result->toArray());
    }
}