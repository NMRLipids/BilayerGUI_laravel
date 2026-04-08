<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200)->assertSee('FAIRMD Lipids Databank');
});

test('the database contains the correct number of trajectories', function () {
    $this->assertDatabaseCount('trajectories', 4);
});

test('the application shows correct number of trajectories', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    // Normalize NBSP + whitespace and assert with regex.
    $content = html_entity_decode($response->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = str_replace("\xC2\xA0", ' ', $content);
    $content = preg_replace('/\s+/u', ' ', $content);
    // get number of trajectories from the database and assert it is shown correctly
    $trajectoriesCount = DB::table('trajectories')->count();
    expect((bool) preg_match('/Total\s+trajectories\s*:\s*(?:<br\s*\/?>\s*)?' . $trajectoriesCount . '/i', $content))->toBeTrue();
});