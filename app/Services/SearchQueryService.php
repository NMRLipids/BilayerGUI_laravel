<?php

namespace App\Services;

use App\Experiments;
use App\Ion;
use App\Lipido;
use App\Trayectoria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only text search across lipids, ions, experiments and trajectories.
 *
 * Ported from SearchController so it can be reused by the MCP server without
 * rendering a view.
 */
class SearchQueryService
{
    private function escapeLike(string $text): string
    {
        $text = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);

        return str_replace(['*', '?'], ['%', '_'], $text);
    }

    private function escapeLikeLiteral(string $text): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
    }

    /**
     * @return array{0:string, 1:bool} [strippedText, isLiteral]
     */
    private function parseQuotedSearch(string $text): array
    {
        if (
            (str_starts_with($text, '"') && str_ends_with($text, '"')) ||
            (str_starts_with($text, "'") && str_ends_with($text, "'"))
        ) {
            return [substr($text, 1, -1), true];
        }

        return [$text, false];
    }

    private function looksLikeDOI(string $text): bool
    {
        return (bool) preg_match('/^10\.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', $text);
    }

    /**
     * Search the database for the given text.
     *
     * @return array{
     *     query:string,
     *     trajectory_redirect_id:int|null,
     *     lipids:Collection,
     *     ions:Collection,
     *     experiments:Collection,
     *     trajectories:Collection
     * }
     */
    public function search(string $text): array
    {
        $texto = trim($text);

        $redirectId = null;
        if (str_starts_with(strtoupper($texto), 'ID')) {
            $id = substr($texto, 2);
            if (is_numeric($id)) {
                $redirectId = (int) $id;
            }
        }

        [$searchText, $isLiteral] = $this->parseQuotedSearch($texto);

        $likeText = $isLiteral ? $this->escapeLikeLiteral($searchText) : $this->escapeLike($searchText);
        $likePattern = $isLiteral ? $likeText : "%{$likeText}%";

        $lipidos = Lipido::where('molecule', 'LIKE', $likePattern)
            ->orWhere('name', 'LIKE', $likePattern)->get();

        $lipidos[] = Lipido::whereHas('properties', function ($query) use ($likePattern) {
            $query->where('value', 'LIKE', $likePattern);
        })->get();
        $lipidos[] = Lipido::whereHas('cross_references', function ($query) use ($likePattern) {
            $query->where('external_id', 'LIKE', $likePattern);
        })->get();
        $lipidos[] = Lipido::whereHas('synonyms', function ($query) use ($likePattern) {
            $query->where('synonym', 'LIKE', $likePattern);
        })->get();
        $lipidos = collect($lipidos)->flatten()->unique('id');
        $lipidIds = $lipidos->pluck('id')->toArray();

        $iones = Ion::where('molecule', 'LIKE', $likePattern)->get()->unique('molecule');

        $experiments = collect();
        $trayectorias = collect();
        if (! empty($lipidIds)) {
            $experiments = Experiments::whereHas('membraneComposition', function ($query) use ($lipidIds) {
                $query->whereIn('lipid_id', $lipidIds);
            })->get();

            $trayectorias = Trayectoria::whereHas('lipidos', function ($query) use ($lipidIds) {
                $query->whereIn('lipid_id', $lipidIds);
            })->get();
        }

        $experiments = $experiments->merge(
            Experiments::where('path', 'LIKE', $likePattern)->get()
        )->unique('id');

        $trayectorias = $trayectorias->merge(
            Trayectoria::where('system', 'LIKE', $likePattern)
                ->orWhere('publication', 'LIKE', $likePattern)
                ->orWhere('author', 'LIKE', $likePattern)
                ->orWhere('git_path', 'LIKE', $likePattern)
                ->get()
        )->unique('id');

        if ($this->looksLikeDOI($texto) || str_starts_with(strtolower($texto), 'doi:') || $lipidos->isEmpty()) {
            $doiText = str_starts_with(strtolower($texto), 'doi:') ? substr($texto, 4) : $texto;
            $doiLike = $isLiteral
                ? $this->escapeLikeLiteral($doiText)
                : "%{$this->escapeLike($doiText)}%";
            $experiments = $experiments->merge(
                Experiments::where('article_doi', 'LIKE', $doiLike)
                    ->orWhere('data_doi', 'LIKE', $doiLike)->get()
            )->unique('id');
            $trayectorias = $trayectorias->merge(
                Trayectoria::where('doi', 'LIKE', $doiLike)->get()
            )->unique('id');
        }

        return [
            'query' => $texto,
            'trajectory_redirect_id' => $redirectId,
            'lipids' => $lipidos->values(),
            'ions' => $iones->values(),
            'experiments' => $experiments->values(),
            'trajectories' => $trayectorias->values(),
        ];
    }

    /**
     * Fetch full detail for a single lipid by numeric id or molecule name.
     *
     * @return array<string, mixed>|null
     */
    public function getLipid(string $idOrMolecule): ?array
    {
        if ($idOrMolecule === '') {
            return null;
        }

        $lipid = is_numeric($idOrMolecule)
            ? DB::table('lipids')->where('id', $idOrMolecule)->first()
            : DB::table('lipids')->where('molecule', $idOrMolecule)->first();

        if (! $lipid) {
            return null;
        }

        $lipidId = $lipid->id;

        $synonyms = DB::table('lipids_synonyms')
            ->where('lipid_id', $lipidId)
            ->pluck('synonym')
            ->toArray();

        $properties = DB::table('lipid_properties')
            ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
            ->select('name', 'value', 'unit')
            ->distinct()
            ->where('lipid_id', $lipidId)
            ->where('name', '!=', 'description')
            ->get();

        $crossRefs = DB::table('cross_references')
            ->where('lipid_id', $lipidId)
            ->join('db', 'cross_references.db_id', '=', 'db.id')
            ->select('db.name as database', 'cross_references.external_id', 'cross_references.external_url as url')
            ->get();

        $description = $lipid->description ?? DB::table('lipid_properties')
            ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
            ->where('lipid_id', $lipidId)
            ->where('name', 'description')
            ->value('value');

        return [
            'id' => $lipidId,
            'name' => $lipid->name ?? null,
            'molecule' => $lipid->molecule ?? null,
            'description' => $description,
            'synonyms' => $synonyms,
            'properties' => $properties->map(fn ($p) => [
                'name' => $p->name,
                'value' => $p->value,
                'unit' => $p->unit ?? null,
            ])->values()->all(),
            'cross_references' => $crossRefs->map(fn ($x) => [
                'database' => $x->database,
                'external_id' => $x->external_id,
                'url' => $x->url,
            ])->values()->all(),
            'url' => url('/lipids/'.$lipidId),
        ];
    }

    /**
     * Fetch full detail for a single experiment by type (FF|OP) and path.
     *
     * @return array<string, mixed>|null
     */
    public function getExperiment(string $type, string $path): ?array
    {
        $type = strtoupper($type);
        if (! in_array($type, ['FF', 'OP'], true) || $path === '') {
            return null;
        }

        $experiment = DB::table('experiments')
            ->where('path', $path)
            ->where('type', $type)
            ->first();

        if (! $experiment) {
            return null;
        }

        $properties = DB::table('experiment_property as ep')
            ->join('experiments_properties_linker as efl', 'ep.id', '=', 'efl.property_id')
            ->where('efl.experiment_id', $experiment->id)
            ->select('ep.name', 'ep.value', 'ep.unit', 'ep.type', 'ep.description')
            ->get()
            ->map(function ($prop) {
                if ($prop->type === 'array' || $prop->type === 'dict') {
                    $prop->value = json_decode($prop->value, true);
                }

                return [
                    'name' => $prop->name,
                    'value' => $prop->value,
                    'unit' => $prop->unit ?? null,
                    'description' => $prop->description ?? null,
                ];
            })
            ->all();

        $membraneComposition = DB::table('experiments_membrane_composition as emc')
            ->join('lipids as l', 'emc.lipid_id', '=', 'l.id')
            ->where('emc.experiment_id', $experiment->id)
            ->select('l.id', 'l.name', 'l.molecule', 'emc.mol_fraction')
            ->get()
            ->map(fn ($row) => [
                'lipid_id' => $row->id,
                'name' => $row->name,
                'molecule' => $row->molecule,
                'mol_fraction' => $row->mol_fraction,
            ])
            ->all();

        return [
            'id' => $experiment->id,
            'type' => $experiment->type,
            'path' => $experiment->path,
            'article_doi' => $experiment->article_doi,
            'data_doi' => $experiment->data_doi,
            'properties' => $properties,
            'membrane_composition' => $membraneComposition,
            'url' => url('/experiments/'.$experiment->type.'/'.$experiment->path),
        ];
    }

    /**
     * Reduce a lipid model to a compact summary array.
     *
     * @return array<string, mixed>
     */
    public static function summarizeLipid(Lipido $lipid): array
    {
        return [
            'id' => $lipid->id,
            'name' => $lipid->name,
            'molecule' => $lipid->molecule,
            'url' => url('/lipids/'.$lipid->id),
        ];
    }
}
