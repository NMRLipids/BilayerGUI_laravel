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

#[Name('get-trajectory')]
#[Title('Get a trajectory (simulation) by ID')]
#[Description('Fetch full detail for a single MD trajectory by its numeric ID, including composition, force field and quality metrics. Set include_plot_data to also return the (large) area-per-lipid and form-factor plot arrays.')]
#[IsReadOnly]
#[IsIdempotent]
class GetTrajectoryTool extends Tool
{
    public function __construct(
        protected SimulationQueryService $simulations,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'include_plot_data' => 'nullable|boolean',
        ], [
            'id.required' => 'You must provide the numeric trajectory ID.',
        ]);

        $trajectory = $this->simulations->getById(
            $validated['id'],
            $validated['include_plot_data'] ?? false,
        );

        if ($trajectory === null) {
            return Response::error("No trajectory found with ID {$validated['id']}.");
        }

        return Response::structured($trajectory);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The numeric trajectory ID.')
                ->required(),
            'include_plot_data' => $schema->boolean()
                ->description('Include large area-per-lipid and form-factor plot arrays. Defaults to false.')
                ->default(false),
        ];
    }
}
