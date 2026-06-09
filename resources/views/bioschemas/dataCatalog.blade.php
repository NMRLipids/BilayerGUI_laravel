
{{-- Render Machine-readable JSON-LD --}}
{{-- Only compute this once --}}
@cache_forever('bioschemas-data-catalog-json-ld')
@php
    $dcp = [];
    if (file_exists(resource_path('site-metadata/dataCatalogProfile.json'))) {
        $dcp = json_decode(file_get_contents(resource_path('site-metadata/dataCatalogProfile.json')), true);
        # Error checking for JSON decoding issues
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error decoding JSON from resources/site-metadata/dataCatalogProfile.json: ' . json_last_error_msg());
            $dcp = []; # reset to empty if JSON is invalid
        }
              
        # Ensure @id and url are set to the current page URL if they are empty in the JSON file
        if (empty($dcp['@id']) || empty($dcp['url'])) {
            if (app()->environment('production')) {
                error_log('Warning: @id or url is missing in resources/site-metadata/dataCatalogProfile.json.');
                error_log('These fields should be populated with the current page URL for proper schema.org metadata in a production environment.');
            }
        }
        $baseUrl = config('app.url');
        $dcp['@id'] = (empty($dcp['@id'])) ? $baseUrl : $dcp['@id']; 
        $dcp['url'] = (empty($dcp['url'])) ? $baseUrl : $dcp['url'];
    } # skip if file is missing, $dcp will be empty and no JSON-LD will be rendered
@endphp
@if (!empty($dcp))
    <script type="application/ld+json">
        @json($dcp)
    </script>
@endif

@endcache_forever('bioschemas-data-catalog-json-ld')
