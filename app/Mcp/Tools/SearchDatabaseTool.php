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

#[Name('search-database')]
#[Title('Search the FAIRMD Lipids database')]
#[Description('Free-text search across lipids, ions, experiments and trajectories. Accepts plain text (partial match with * and ? wildcards), quoted text for exact match, a DOI, or "ID<number>" to look up a trajectory directly.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchDatabaseTool extends Tool
{
    public function __construct(
        protected SearchQueryService $search,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'text' => 'required|string|max:255',
        ], [
            'text.required' => 'You must provide search text, e.g. a lipid name like "POPC", a DOI, or "ID42".',
        ]);

        $result = $this->search->search($validated['text']);

        return Response::structured([
            'query' => $result['query'],
            'trajectory_redirect_id' => $result['trajectory_redirect_id'],
            'counts' => [
                'lipids' => $result['lipids']->count(),
                'ions' => $result['ions']->count(),
                'experiments' => $result['experiments']->count(),
                'trajectories' => $result['trajectories']->count(),
            ],
            'lipids' => $result['lipids']->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'molecule' => $l->molecule,
            ])->all(),
            'ions' => $result['ions']->map(fn ($i) => [
                'id' => $i->id,
                'molecule' => $i->molecule,
            ])->all(),
            'experiments' => $result['experiments']->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'path' => $e->path,
                'article_doi' => $e->article_doi,
            ])->all(),
            'trajectories' => $result['trajectories']->map(fn ($t) => [
                'id' => $t->id,
                'doi' => $t->doi,
                'temperature' => $t->temperature,
            ])->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()
                ->description('The text to search for. Examples: "POPC", a DOI like "10.1234/abcd", or "ID42" to fetch trajectory 42.')
                ->required(),
        ];
    }
}
