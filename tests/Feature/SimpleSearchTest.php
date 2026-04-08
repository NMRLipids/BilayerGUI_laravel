<?php

test('simple search returns a successful response for POPC', function () {
	$response = $this->get('/search?text=POPC');

	$response->assertStatus(200)->assertSee('POPC');
});

test('simple search returns a successful response for beta-octyl D-glucopyranoside', function () {
    $response = $this->get('/search?text=beta-octyl D-glucopyranoside');

    $response->assertStatus(200);
});

test('simple search returns a successful response for an InChI-key', function () {
    $response = $this->get('/search?text=AFSHUZFNMVJNKX-LLWMBOQKSA-N');

    $response->assertStatus(200)->assertSeeText('DOG');
});

test('simple search returns no results for non-existing molecule', function () {
    $response = $this->get('/search?text=non-existing-molecule');

    $response->assertStatus(200)->assertSeeText('Your query has returned no data.');
});