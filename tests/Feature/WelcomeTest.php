<?php
use Illuminate\Support\Facades\Config;

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

test('the header contains the json-ld data catalog markup', function () {
    // The Blade view only includes JSON-LD in production; temporarily set the environment.
    Config::set('app.env', 'production');

    $response = $this->get('/');
    $response->assertStatus(200);

    $content = $response->getContent();

    // Extract the JSON-LD script block from the page
    $found = preg_match(
        '/<script\s+type="application\/ld\+json">\s*([\s\S]*?)\s*<\/script>/i',
        $content,
        $matches
    );
    expect($found)->toBe(1, 'Page should contain a JSON-LD <script> tag');

    $actual = json_decode($matches[1], true);
    expect($actual)->not->toBeNull('JSON-LD content should be valid JSON');

    // Build expected data from the profile JSON file, applying the same
    // @id override logic used in resources/views/bioschemas/dataCatalog.blade.php
    $expected = json_decode(
        file_get_contents(resource_path('site-metadata/dataCatalogProfile.json')),
        true
    );
    if (empty($expected['@id'])) {
        $expected['@id'] = config('app.url');
    }

    expect($actual)->toEqual($expected);
});