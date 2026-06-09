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

#[Name('advanced-trajectory-search')]
#[Title('Advanced trajectory (simulation) search')]
#[Description('Filter MD trajectories by lipid/ion/membrane composition, force field and numeric ranges (temperature and quality metrics), with sorting and pagination. Composition filters accept per-item logical operators (and/or/not).')]
#[IsReadOnly]
#[IsIdempotent]
class AdvancedTrajectorySearchTool extends Tool
{
    public function __construct(
        protected SimulationQueryService $simulations,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'lipids' => 'array',
            'lipids.*' => 'string|max:255',
            'lipids_operator' => 'array',
            'lipids_operator.*' => 'in:and,or,not',
            'ions' => 'array',
            'ions.*' => 'string|max:255',
            'ions_operator' => 'array',
            'ions_operator.*' => 'in:and,or,not',
            'membranes' => 'array',
            'membranes.*' => 'integer',
            'trajectory_ids' => 'array',
            'trajectory_ids.*' => 'integer',
            'force_fields' => 'array',
            'force_fields.*' => 'string|max:255',
            'force_fields_operator' => 'array',
            'force_fields_operator.*' => 'in:equals,contains,starts_with,ends_with',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'area_per_lipid_min' => 'nullable|numeric',
            'area_per_lipid_max' => 'nullable|numeric',
            'quality_total_min' => 'nullable|numeric',
            'quality_total_max' => 'nullable|numeric',
            'quality_hg_min' => 'nullable|numeric',
            'quality_hg_max' => 'nullable|numeric',
            'quality_tails_min' => 'nullable|numeric',
            'quality_tails_max' => 'nullable|numeric',
            'bilayer_thickness_min' => 'nullable|numeric',
            'bilayer_thickness_max' => 'nullable|numeric',
            'ff_quality_min' => 'nullable|numeric',
            'ff_quality_max' => 'nullable|numeric',
            'sort' => 'nullable|in:id,temperature,length,area_per_lipid,op_quality_total,ff_quality',
            'direction' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // The underlying query only applies lipid/ion filtering when the matching
        // operator array is present. Mirror the documented default of "and" so that
        // callers can omit the operator arrays and still get composition filtering.
        if (! empty($validated['lipids']) && empty($validated['lipids_operator'])) {
            $validated['lipids_operator'] = array_fill(0, count($validated['lipids']), 'and');
        }
        if (! empty($validated['ions']) && empty($validated['ions_operator'])) {
            $validated['ions_operator'] = array_fill(0, count($validated['ions']), 'and');
        }

        $filters = array_filter([
            'lipidos' => $validated['lipids'] ?? null,
            'lipidos_operador' => $validated['lipids_operator'] ?? null,
            'iones' => $validated['ions'] ?? null,
            'iones_operador' => $validated['ions_operator'] ?? null,
            'membranas' => $validated['membranes'] ?? null,
            'trayectoria' => $validated['trajectory_ids'] ?? null,
            'trayectoria_force_field' => $validated['force_fields'] ?? null,
            'trayectoria_force_field_operador' => $validated['force_fields_operator'] ?? null,
            'temperature-start' => $validated['temperature_min'] ?? null,
            'temperature-end' => $validated['temperature_max'] ?? null,
            'Area_per_lipid-start' => $validated['area_per_lipid_min'] ?? null,
            'Area_per_lipid-end' => $validated['area_per_lipid_max'] ?? null,
            'quality_total-start' => $validated['quality_total_min'] ?? null,
            'quality_total-end' => $validated['quality_total_max'] ?? null,
            'quality_hg-start' => $validated['quality_hg_min'] ?? null,
            'quality_hg-end' => $validated['quality_hg_max'] ?? null,
            'quality_tails-start' => $validated['quality_tails_min'] ?? null,
            'quality_tails-end' => $validated['quality_tails_max'] ?? null,
            'Bilayer_thickness-start' => $validated['bilayer_thickness_min'] ?? null,
            'Bilayer_thickness-end' => $validated['bilayer_thickness_max'] ?? null,
            'Form_factor_quality-start' => $validated['ff_quality_min'] ?? null,
            'Form_factor_quality-end' => $validated['ff_quality_max'] ?? null,
        ], fn ($v) => $v !== null);

        $result = $this->simulations->advancedSearch(
            $filters,
            $validated['sort'] ?? 'id',
            $validated['direction'] ?? 'asc',
            $validated['page'] ?? 1,
            $validated['per_page'] ?? 15,
        );

        return Response::structured([
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
            'results' => $result['data']->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lipids' => $schema->array()->items($schema->string())
                ->description('Lipid molecule names to filter by, e.g. ["POPC", "CHOL"]. Use the special value "is_missing" to match trajectories with no lipid.'),
            'lipids_operator' => $schema->array()->items($schema->string()->enum(['and', 'or', 'not']))
                ->description('Per-lipid logical operator, aligned by index with "lipids". Defaults to "and".'),
            'ions' => $schema->array()->items($schema->string())
                ->description('Ion molecule names to filter by, e.g. ["SOD", "CLA"].'),
            'ions_operator' => $schema->array()->items($schema->string()->enum(['and', 'or', 'not']))
                ->description('Per-ion logical operator, aligned by index with "ions". Defaults to "and".'),
            'membranes' => $schema->array()->items($schema->integer())
                ->description('Membrane IDs to filter by.'),
            'trajectory_ids' => $schema->array()->items($schema->integer())
                ->description('Restrict results to these trajectory IDs.'),
            'force_fields' => $schema->array()->items($schema->string())
                ->description('Force field name patterns to filter by.'),
            'force_fields_operator' => $schema->array()->items($schema->string()->enum(['equals', 'contains', 'starts_with', 'ends_with']))
                ->description('Per-force-field match mode, aligned by index with "force_fields". Defaults to "equals".'),
            'temperature_min' => $schema->number()->description('Minimum temperature in K (use together with temperature_max).'),
            'temperature_max' => $schema->number()->description('Maximum temperature in K.'),
            'area_per_lipid_min' => $schema->number()->description('Minimum area per lipid.'),
            'area_per_lipid_max' => $schema->number()->description('Maximum area per lipid.'),
            'quality_total_min' => $schema->number()->description('Minimum total OP quality.'),
            'quality_total_max' => $schema->number()->description('Maximum total OP quality.'),
            'quality_hg_min' => $schema->number()->description('Minimum headgroup OP quality.'),
            'quality_hg_max' => $schema->number()->description('Maximum headgroup OP quality.'),
            'quality_tails_min' => $schema->number()->description('Minimum tails OP quality.'),
            'quality_tails_max' => $schema->number()->description('Maximum tails OP quality.'),
            'bilayer_thickness_min' => $schema->number()->description('Minimum bilayer thickness.'),
            'bilayer_thickness_max' => $schema->number()->description('Maximum bilayer thickness.'),
            'ff_quality_min' => $schema->number()->description('Minimum form-factor (FF) quality.'),
            'ff_quality_max' => $schema->number()->description('Maximum form-factor (FF) quality.'),
            'sort' => $schema->string()->enum(['id', 'temperature', 'length', 'area_per_lipid', 'op_quality_total', 'ff_quality'])
                ->description('Sort column.')->default('id'),
            'direction' => $schema->string()->enum(['asc', 'desc'])->description('Sort direction.')->default('asc'),
            'page' => $schema->integer()->description('Page number (1-based).')->default(1),
            'per_page' => $schema->integer()->description('Results per page (max 100).')->default(15),
        ];
    }
}
