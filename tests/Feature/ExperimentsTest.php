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

test('the NMR metadata of an OP experiment is shown in the overview tab', function () {
    $response = $this->get('experiment/OP/10.1021/acs.jpcb.4c04719/1');

    $response->assertStatus(200)
    ->assertSee('NMR method')
    ->assertSee('PDLF:R18_1^7')
    ->assertSee('NMR instrument')
    ->assertSee('Bruker Avance III HD 500 MHz, Triple Resonance CP-MAS Probe (4mm)')
    ->assertSee('NMR RF heating correction')
    ->assertSee('NMR sign measured')
    ->assertSee('NMR details')
    ->assertSee('1H-13C R-PDLF with R18_1^7 recoupling and INEPT readout at 5 kHz MAS')
    ;
});

test('the properties tab is reachable when there are properties left to show', function () {
    // Regression test for #141: the tab button was permanently hidden because it
    // was guarded by an undefined variable, which buried the NMR metadata with it.
    $response = $this->get('experiment/OP/10.1021/acs.jpcb.4c04719/1');

    $response->assertStatus(200)
    ->assertDontSee('class="nav-link d-none" id="properties-tab"', escape: false)
    ;
});

test('the X-ray metadata of an FF experiment is shown in the overview tab', function () {
    $response = $this->get('experiment/FF/10.1016/j.chemphyslip.2020.104892/1');

    $response->assertStatus(200)
    ->assertSee('X-ray wavelength (Å)')
    ->assertSee('X-ray beam size')
    ->assertSee('0.24 x 0.24 mm')
    // PIXEL_SIZE and SAMPLE_CONTAINER have no dedicated row and no schema entry,
    // so they must fall through to the generic rows at the end of the block.
    ->assertSee('X-ray pixel size')
    ->assertSee('X-ray sample container')
    ->assertSee('1 mm quartz capillary')
    ;
});