<?php

test('the simulations page returns a successful response', function () {
    $response = $this->get('/simulations');

    $response->assertStatus(200)->assertSee('Simulations')
    ->assertSee('POPC')
    ->assertSee('Berger')
    ;
});

test('the best sorting option is working correctly', function () {
    $response = $this->get('/simulations?sort=best&direction=desc');

    $response->assertStatus(200)
    ->assertSeeInOrder(['805','593','183']);

    
});

test('the trajectory details page returns a successful response', function () {
    $response = $this->get('/simulations/805');

    $response->assertStatus(200)
    ->assertSee('Order parameters quality = 0.805')
    ->assertSee('Order Parameters POPC')
    ->assertSee('10.1021/acs.jpcb.4c04719/4') // Check that the DOI is shown as a link.
    ;
});