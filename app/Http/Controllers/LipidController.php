<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class LipidController extends Controller
{
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
        
        // get additional properties if needed
        $properties = DB::table('lipid_properties')
            ->join('properties', 'lipid_properties.property_id', '=', 'properties.id')
            ->select('name','value','unit')
            ->where('lipid_id', $lipid_id)
            ->where('name', '!=', 'description')
            ->get();
        // If description is part of properties, move it to main lipid data
        

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


     return View::make('lipid', ['lipid' => $lipids_data]);


        

    }
}
