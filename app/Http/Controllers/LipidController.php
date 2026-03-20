<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Lipido;

class LipidController extends Controller
{

    private function formatJsonLd(array $entity): string
    {

        // Prepare data for JSON-LD
        $props = $entity['properties'] ?? [];
        // Flatten properties into a key-value array for easier access
        foreach ($props as $prop) {
            $entity['properties_flat'][$prop->name] = $prop->value . ($prop->unit ?? '');
        }

        $jsonAr = array_filter([
            '@context' => 'https://schema.org/',
            '@type' => 'MolecularEntity',
            '@id' => $entity['idUrl'] ?? url()->current(),
            'http://purl.org/dc/terms/conformsTo' => [
                '@id' => 'https://bioschemas.org/profiles/MolecularEntity/0.5-RELEASE',
                '@type' => 'CreativeWork'
            ],
            'identifier' => $entity['identifier'] ?? null,
            'name' => $entity['name'] ?? null,
            'url' => $entity['url'] ?? url()->current(),
            'inChI' => $entity['properties_flat']['inChI'] ?? null,
            'inChIKey' => $entity['properties_flat']['inChIKey'] ?? null,
            'iupacName' => $entity['properties_flat']['iupacName'] ?? null,
            'molecularFormula' => $entity['properties_flat']['molecularFormula'] ?? null,
            'molecularWeight' => $entity['properties_flat']['MolecularWeight'] ?? null,
            'smiles' => $entity['properties_flat']['smiles'] ?? null,
            'alternateName' => $entity['synonyms'] ?? null,
            'description' => $entity['properties_flat']['description'] ?? null,
            'image' => $entity['properties_flat']['image'] ?? null,
            'sameAs' => $entity['properties_flat']['sameAs'] ?? null,
            'biologicalRole' => $entity['properties_flat']['biologicalRole'] ?? null,
            'chemicalRole' => $entity['properties_flat']['chemicalRole'] ?? null,
            'bioChemInteraction' => $entity['properties_flat']['bioChemInteraction'] ?? null,
            'bioChemSimilarity' => $entity['properties_flat']['bioChemSimilarity'] ?? null,
        ], fn($v) => !is_null($v));

        // Add cross-references
        if (isset($entity['cross_references']) && is_iterable($entity['cross_references'])) {
            foreach ($entity['cross_references'] as $xref) {
                if (isset($xref)) {
                    $jsonAr['sameAs'][] = $xref->url ?:
                        "https://identifiers.org/{$xref->database}/{$xref->external_id}";
                }
            }
        }
        return json_encode($jsonAr, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    

    public function list(Request $request): \Illuminate\View\View 
    {
        $itemsPerPage = $request->query('items_per_page', 10);
        $embed = $request->query('embed', false);
        $embed = filter_var($embed, FILTER_VALIDATE_BOOLEAN);
        // Special value to show all
        if ($itemsPerPage === 'all' || $itemsPerPage == -1) {
            $lipids = Lipido::all() //sorted by molecule name
                ->sortBy('molecule')
                ->values();
            // Pass a flag to the view so it knows not to render pagination controls
            if ($embed) {
                return view('lipids.embed', ['lipids' => $lipids, 'showAll' => true]);
            } else {    
                return view('lipids.list', ['lipids' => $lipids, 'showAll' => true]);
            }
        }

        
        // Validate for normal pagination
        if (!is_numeric($itemsPerPage) || $itemsPerPage < 1 || $itemsPerPage > 100) {
            $itemsPerPage = 10; // default to 10 if invalid
        }
        $lipids = Lipido::orderBy('molecule', 'asc')->paginate($itemsPerPage);

        if ($embed) {
            return view('lipids.embed', ['lipids' => $lipids, 'showAll' => false]);
        } else {    
        return view('lipids.list', [
            'lipids' => $lipids, 
            'showAll' => false, 
             ]);
        }
    }
    /**
     * Show the data for a given lipid profile.
     */
    public function show(string $lipid_id) : \Illuminate\View\View    {

        // Fetch lipid data from the database based on the provided lipid_id
        // For example:
        if (empty($lipid_id)) {
            abort(400, 'Lipid ID cannot be empty');
        }
        if (is_numeric($lipid_id)) {
            $lipid = DB::table('lipids')->where('id', $lipid_id)->first();
        } else {
            $lipid = DB::table('lipids')->where('molecule', $lipid_id)->first();
        }   
        if (!$lipid) {
            abort(404, "Lipid '$lipid_id' not found");
        }
        // Get the base data each lipid has from the DB.
        $lipid_id = $lipid->id ?? null;
        $propdescription = DB::table('lipid_properties')
                ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
                ->select('value','name')
                ->where('lipid_id', '=', $lipid_id)
                ->where('name', 'like', 'description')
                ->where('value', '!=', '')
                ->first();
        $lipids_data= [
            'id' => $lipid_id,
            'name' => $lipid->name ?? 'Nonexistent Lipid',
            'molecule' => $lipid->molecule ?? 'Unknown Molecule',
        ];
        $des =  $lipid->description ?? $propdescription?->value; 
        if (isset($des)) {
            $lipids_data['description'] = $des;
    };       
        $lipids_data['synonyms'] = DB::table('lipids_synonyms')
            ->where('lipid_id', $lipid_id)
            ->pluck('synonym')
            ->toArray();
        // get additional properties if needed
        $properties = DB::table('lipid_properties')
            ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
            ->select('name','value','unit')
            ->where('lipid_id', $lipid_id)
            ->where('name', '!=', 'description')
            ->get();
        // If description is part of properties, move it to main lipid data
        $descriptionProperty = $properties->firstWhere('name', 'description');
        if ($descriptionProperty) {
            $lipids_data['description'] = $descriptionProperty->value;
            $properties = $properties->filter(function ($prop) {
                return $prop->name !== 'description';
            });
        }


        // Move description from properties to main lipid data if exists
        
        // add properties to lipid data
        $lipids_data['properties'] = $properties;
        // Add cross-references if any
        $cross_refs = DB::table('cross_references')
            ->where('lipid_id', $lipid_id)
            ->join('db', 'cross_references.db_id', '=', 'db.id')
            ->select('db.name as database', 'cross_references.external_id', 'cross_references.external_url as url')
            ->get();
        $lipids_data['cross_references'] = $cross_refs;
        // Format data for bioschemas
        $lipids_data['properties_flat'] = [];
        foreach ($properties as $prop) {
            $lipids_data['properties_flat'][$prop->name] = $prop->value . (isset($prop->unit) ? ' ' . $prop->unit : '');
        }
        $lipids_data['jsonLd'] = $this->formatJsonLd($lipids_data);
        // Return a view with the lipid data    

     return View::make('lipids.show', ['entity' => $lipids_data]);


        

    }
}
