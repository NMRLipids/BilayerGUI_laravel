<?php

namespace App\Mcp\Tools;

use App\Services\StatisticsService;
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

#[Name('database-statistics')]
#[Title('Database statistics and totals')]
#[Description('Return high-level totals for the database (number of trajectories, membranes and experiments, plus the last update time). Optionally include a per-force-field breakdown of membranes.')]
#[IsReadOnly]
#[IsIdempotent]
class DatabaseStatisticsTool extends Tool
{
    public function __construct(
        protected StatisticsService $statistics,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'include_force_field_breakdown' => 'nullable|boolean',
        ]);

        $payload = $this->statistics->totals();

        if ($validated['include_force_field_breakdown'] ?? false) {
            $payload['force_field_breakdown'] = $this->statistics->forceFieldBreakdown();
        }

        return Response::structured($payload);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'include_force_field_breakdown' => $schema->boolean()
                ->description('Include the number of membranes per force field. Defaults to false.')
                ->default(false),
        ];
    }
}
