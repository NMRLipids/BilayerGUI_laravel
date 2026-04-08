<?php

test('the advanced search page returns a successful response', function () {
    $response = $this->get('/new-advanced-search');

    $response->assertStatus(200);
});

test('the advanced search returns correct result for a valid query', function () {
    $response = $this->get('/new-advanced-search/result?lipidos%5B1%5D=POPC&lipidos_operador%5B1%5D=and&lipidos%5B2%5D=POPS&lipidos_operador%5B2%5D=and&trayectoria_force_field%5B1%5D=Berger&trayectoria_force_field_operador%5B1%5D=equals&nothinghere=1' );

    $response->assertStatus(200)->assertSee('184')->assertSee('gromacs')
    ->assertDontSee('805')
    ->assertDontSee('593')
    ;
});