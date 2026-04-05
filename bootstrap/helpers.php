<?php

const OPERADOR_AND = 'and';
const OPERADOR_NOT = 'not';
const OPERADOR_OR = 'or';
const OPERADOR_CONTAINS = 'contains';
const OPERADOR_EQUALS = 'equals';
const OPERADOR_STARTS = 'starts_with';
const OPERADOR_ENDS = 'ends_with';

function resaltar_texto($texto, $texto_para_resaltar) {
    $escaped = preg_quote($texto_para_resaltar, '%');
    $texto_resaltado = preg_replace("%({$escaped})%i", '<b>$1</b>', $texto);
    return $texto_resaltado;
}

/**
 * Render a DOI as a clickable link. Article title is fetched asynchronously
 * via JavaScript and shown as a tooltip on hover.
 */
function renderDOI($doi)
{
    if (empty($doi)) {
        return 'N/A';
    }

    $doi = trim($doi);

    // Normalise: strip common prefixes so only the bare DOI remains.
    $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
    $doi = preg_replace('#^doi:\s*#i', '', $doi);

    // A valid DOI must start with the "10." directory indicator.
    if (!preg_match('#^10\.#', $doi)) {
        return e($doi);
    }

    $url = 'https://doi.org/' .  str_replace('%2F', '/', rawurlencode($doi));
    $escapedDoi = e($doi);

    return '<a href="' . $url . '" target="_blank" rel="noopener" '
        . 'class="doi-link" data-doi="' . $escapedDoi . '">'
        . $escapedDoi . '</a>';
}

/**
 * Render a GitHub URL as a clickable link.
 * Builds the full URL from a path suffix using config('app.github_base_url')
 * or config('app.github_raw_url'), inserting the branch with GitHub's standard
 * URL pattern (blob/{branch} for browsable, refs/heads/{branch} for raw).
 * If the URL does not exist (HTTP 404), returns a message encouraging
 * contribution with a link to the BilayerData contribution page.
 * The branch name is shown as a label next to the link.
 */
function renderGitHubURL(string $path, bool $raw = false, string $branch = 'main', ?string $text = null): string
{
    $baseUrl = rtrim($raw
        ? config('app.github_raw_url')
        : config('app.github_base_url'), '/');

    $branchSegment = $raw
        ? 'refs/heads/' . $branch
        : 'blob/' . $branch;

    $url = $baseUrl . '/' . $branchSegment . '/' . ltrim($path, '/');

    $cacheKey = 'github_url_exists_' . md5($url);

    $exists = cache()->remember($cacheKey, 3600, function () use ($url) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    });

    $linkText = $text ?? $path;
    $branchLabel = ' <span class="badge bg-secondary">' . e($branch) . '</span>';

    if ($exists) {
        return '<a href="' . e($url) . '" target="_blank" rel="noopener">' . e($linkText) . '</a>' . $branchLabel;
    }

    $contributeUrl = config('app.github_contribute_url');

    return '<span class="text-muted">Data not yet available' . $branchLabel . ' &mdash; '
        . '<a href="' . e($contributeUrl) . '" target="_blank" rel="noopener">'
        . 'contribute this data to BilayerData</a></span>';
}

/**
 * Traduccion de las columnas de base de datos a texto
 */
function c($clave) {
    return __('columna.'.\App\Lib\LangUtil::mapeoPropiedadColumna($clave));
}

function none($valor, $sufijo = '')
{
    if($valor === "" || $valor === null) {
        return 'None';
    }

    return $sufijo != '' ? $valor.' '.$sufijo : $valor;
}

