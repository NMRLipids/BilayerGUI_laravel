<?php

test('the experiments page returns a successful response', function () {
    $response = $this->get('/experiments');

    $response->assertStatus(200)->assertSee('Experiments')
    ->assertSee('POPC')
    ->assertSee('10.1039/B418131J')
    ;
});

test('the experiment details page returns a successful response for an OP experiment', function () {
    $response = $this->get('experiment/OP/10.1039/c2cp42738a/6');

    $response->assertStatus(200)
    ->assertSee('Internal ID')
    ->assertSee('POPC')
    ->assertSee('Order Parameters POPC')
    ->assertSee('data-opplot=')
    ;
});

test('the experiment details page returns a successful response for an FF experiment', function () {
    $response = $this->get('experiment/FF/10.1021/acs.jpcb.0c03389/2');

    $response->assertStatus(200)
    ->assertSee('Internal ID')
    ->assertSee('SM16')
    ->assertSee('data-ffdata=')
    ;
});