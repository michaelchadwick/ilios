<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Service\GraphQL\TypeResolver;
use App\Service\GraphQL\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function json_decode;
use function json_encode;

class GraphQL
{
    #[Route('/api/graphql')]
    public function index(Request $request, TypeRegistry $typeRegistry, TypeResolver $resolver): Response
    {
        $types = $typeRegistry->getTypes();
        $queryType = new ObjectType([
            'description' => 'This API is not subject to normal Ilios backwards compatibility rules ' .
                'and should be considered VERY experimental. Use at your own risk ' .
                'and pay strict attention to Ilios release notes before upgrading.',
            'name' => 'Query',
            'fields' => $types,
        ]);
        $schema = new Schema([
            'query' => $queryType,
        ]);
        $input = json_decode($request->getContent() ?: '', true);
        $variableValues = array_key_exists('variables', $input) ? $input['variables'] : null;
        $result = \GraphQL\GraphQL::executeQuery(
            $schema,
            $input['query'] ?? null,
            null,
            null,
            $variableValues,
            null,
            $resolver,
        );
        return JsonResponse::fromJsonString(json_encode($result->toArray()));
    }
}
