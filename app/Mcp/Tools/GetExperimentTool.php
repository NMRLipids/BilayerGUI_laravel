<?php

namespace App\Mcp\Tools;

use App\Services\SearchQueryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-experiment')]
#[Title('Get an experiment by type and path')]
#[Description('Fetch full detail for a single experiment identified by its type (FF = form factor, OP = order parameter) and path, including measured properties and membrane composition.')]
#[IsReadOnly]
#[IsIdempotent]
class GetExperimentTool extends Tool
{
    public function __construct(
        protected SearchQueryService $search,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => 'required|in:FF,OP',
            'path' => 'required|string|max:255',
        ], [
            'type.in' => 'Experiment type must be "FF" (form factor) or "OP" (order parameter).',
        ]);

        $experiment = $this->search->getExperiment($validated['type'], $validated['path']);

        if ($experiment === null) {
            return Response::error("No {$validated['type']} experiment found with path '{$validated['path']}'.");
        }

        return Response::structured($experiment);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['FF', 'OP'])
                ->description('Experiment type: FF (form factor) or OP (order parameter).')
                ->required(),
            'path' => $schema->string()
                ->description('The experiment path identifier.')
                ->required(),
        ];
    }
}
