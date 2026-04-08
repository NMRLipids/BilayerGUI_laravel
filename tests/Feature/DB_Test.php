<?php

test('DB content is loaded correctly', function () 

   
    {
        $inchiKey = 'AFSHUZFNMVJNKX-LLWMBOQKSA-N';

        $lipidId = DB::table('lipids')->where('molecule', 'DOG')->value('id');
        expect($lipidId !== null)->toBeTrue('Lipid DOG not found in database');

        $propertyId = DB::table('properties')->where('value', $inchiKey)->value('id');
        expect($propertyId !== null)->toBeTrue('InChIKey not found in database');

        $exists = DB::table('lipid_properties')
            ->where('lipid_id', $lipidId)
            ->where('property_id', $propertyId)
            ->exists();

        expect($exists)->toBeTrue('Lipid DOG does not have the correct InChIKey property');
    }


);


