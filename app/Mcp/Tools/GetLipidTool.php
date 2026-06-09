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

#[Name('get-lipid')]
#[Title('Get a lipid by ID or molecule name')]
#[Description('Fetch full detail for a single lipid by numeric ID or molecule name, including properties, synonyms and cross-references to external databases.')]
#[IsReadOnly]
#[IsIdempotent]
class GetLipidTool extends Tool
{
    public function __construct(
        protected SearchQueryService $search,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id_or_molecule' => 'required|string|max:255',
        ], [
            'id_or_molecule.required' => 'Provide a numeric lipid ID or a molecule name such as "POPC".',
        ]);

        $lipid = $this->search->getLipid($validated['id_or_molecule']);

        if ($lipid === null) {
            return Response::error("No lipid found for '{$validated['id_or_molecule']}'.");
        }

        return Response::structured($lipid);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id_or_molecule' => $schema->string()
                ->description('A numeric lipid ID (e.g. "12") or a molecule name (e.g. "POPC").')
                ->required(),
        ];
    }
}
