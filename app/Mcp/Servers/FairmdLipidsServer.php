<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AdvancedTrajectorySearchTool;
use App\Mcp\Tools\BestSimulationsTool;
use App\Mcp\Tools\DatabaseStatisticsTool;
use App\Mcp\Tools\GetExperimentTool;
use App\Mcp\Tools\GetLipidTool;
use App\Mcp\Tools\GetTrajectoryTool;
use App\Mcp\Tools\SearchDatabaseTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('FAIRMD Lipids')]
#[Version('0.1.0')]
#[Instructions(<<<'TXT'
This server exposes read-only access to the FAIRMD Lipids database: a collection of
molecular dynamics (MD) simulations of lipid membranes and related NMR/X-ray
experiments.

Domain primitives:
- Trajectories (a.k.a. simulations): an MD run of a lipid bilayer, with a force
  field, temperature, lipid/ion composition and quality metrics.
- Quality metrics live on each trajectory's analysis: op_quality_total /
  op_quality_headgroups / op_quality_tails (agreement with NMR order parameters,
  higher is better) and ff_quality (agreement with X-ray form factors, higher is
  better), plus area_per_lipid and bilayer_thickness.
- Lipids, ions, membranes and experiments (type FF = form factor, OP = order
  parameter).

Tool selection guidance:
- Use `search-database` for free-text lookups (lipid/ion names, DOIs, authors).
- Use `advanced-trajectory-search` to filter trajectories by composition, force
  field and numeric ranges.
- Use `best-simulations` when asked for the "best" simulation(s); it ranks by the
  rank-product of OP and FF quality (lower product = better), optionally for a
  specific lipid.
- Use `get-trajectory`, `get-experiment` and `get-lipid` to fetch full detail by id.
- Use `database-statistics` for totals and force-field breakdowns.

All tools are read-only and never modify data.
TXT)]
class FairmdLipidsServer extends Server
{
    protected array $tools = [
        SearchDatabaseTool::class,
        AdvancedTrajectorySearchTool::class,
        BestSimulationsTool::class,
        GetTrajectoryTool::class,
        GetExperimentTool::class,
        GetLipidTool::class,
        DatabaseStatisticsTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
