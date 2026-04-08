<?php

test('the application returns a successful response', function () {
    $response = $this->get('/lipids');

    $response->assertStatus(200)
    ->assertSee('beta-octyl D-glucopyranoside')
    ->assertSee('N-palmitoyl-D-erythro-sphingosine')
    ->assertSee('DDOPC');
});

test('the correct InChIKey is shown for DOG', function () {
    $response = $this->get('/lipid/DOG');

    $response->assertStatus(200)->assertSee('1,2-dioleoyl-sn-glycerol');
});