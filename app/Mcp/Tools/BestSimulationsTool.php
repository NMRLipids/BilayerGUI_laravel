<?php

namespace App\Mcp\Tools;

use App\Services\SimulationQueryService;
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

#[Name('best-simulations')]
#[Title('Best simulations by quality ranking')]
#[Description('Return the best-ranked MD simulations. Ranking uses the rank-product of OP quality (agreement with NMR order parameters) and FF quality (agreement with X-ray form factors); a lower rank product means a better simulation. Optionally restrict to simulations containing a specific lipid. Higher OP and FF scores mean better agreement. "ranked_count" counts ranked (scored) simulations only; use advanced-trajectory-search for the full population.')]
#[IsReadOnly]
#[IsIdempotent]
class BestSimulationsTool extends Tool
{
    public function __construct(
        protected SimulationQueryService $simulations,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'lipid' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->simulations->bestSimulations(
            $validated['lipid'] ?? null,
            $validated['limit'] ?? 10,
        );

        return Response::structured([
            'ranking' => 'rank-product of OP and FF quality scores (lower rank-product is better; higher OP and FF scores mean better agreement.)',
            'lipid' => $validated['lipid'] ?? null,
            'ranked_count' => $result['total'],
            'results' => $result['data']->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lipid' => $schema->string()
                ->description('Optional lipid molecule name to rank simulations for, e.g. "POPC".'),
            'limit' => $schema->integer()
                ->description('Maximum number of simulations to return (max 100).')
                ->default(10),
        ];
    }
}
