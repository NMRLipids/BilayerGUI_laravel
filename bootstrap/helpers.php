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
    $url = 'https://doi.org/' . rawurlencode($doi);
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

function dumpSql($sql_raw)
{
    if( empty($sql_raw) || !is_string($sql_raw) )
    {
        return false;
    }

    $sql_reserved_all = array (
        'ACCESSIBLE', 'ACTION', 'ADD', 'AFTER', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AND', 'AS', 'ASC',
        'AUTOCOMMIT', 'AUTO_INCREMENT', 'AVG_ROW_LENGTH', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'BY', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED',
        'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT',
        'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE',
        'DAY_SECOND', 'DEFINER', 'DELAYED', 'DELAY_KEY_WRITE', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
        'DO', 'DROP', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXECUTE',
        'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULL', 'FULLTEXT',
        'FUNCTION', 'GEMINI', 'GEMINI_SPIN_RETRIES', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP', 'HAVING', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
        'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INNER', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
        'INTO', 'INVOKER', 'IS', 'ISOLATION', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEFT', 'LEVEL', 'LIKE', 'LIMIT', 'LINEAR',
        'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
        'MASTER_LOG_POS', 'MASTER_PASSWORD', 'MASTER_PORT', 'MASTER_USER', 'MATCH', 'MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR',
        'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS', 'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
        'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
        'ORDER', 'OUTER', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
        'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE', 'RAID_TYPE', 'RANGE', 'READ', 'READ_ONLY',
        'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
        'RETURN', 'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SELECT', 'SEPARATOR',
        'SERIALIZABLE', 'SESSION', 'SET', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL', 'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
        'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF',
        'SQL_LOG_UPDATE', 'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_NO_CACHE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES',
        'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER', 'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
        'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL',
        'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNION', 'UNIQUE', 'UNLOCK', 'UPDATE', 'USAGE', 'USE', 'USING', 'VALUES', 'VARIABLES',
        'VIEW', 'WHEN', 'WHERE', 'WITH', 'WORK', 'WRITE', 'XOR', 'YEAR_MONTH'
    );

    $sql_skip_reserved_words = array('AS', 'ON', 'USING');
    $sql_special_reserved_words = array('(', ')');

    $sql_raw = str_replace("\n", " ", $sql_raw);

    $sql_formatted = "";

    $prev_word = "";
    $word = "";

    for( $i=0, $j = strlen($sql_raw); $i < $j; $i++ )
    {
        $word .= $sql_raw[$i];

        $word_trimmed = trim($word);

        if($sql_raw[$i] == " " || in_array($sql_raw[$i], $sql_special_reserved_words))
        {
            $word_trimmed = trim($word);

            $trimmed_special = false;

            if( in_array($sql_raw[$i], $sql_special_reserved_words) )
            {
                $word_trimmed = substr($word_trimmed, 0, -1);
                $trimmed_special = true;
            }

            $word_trimmed = strtoupper($word_trimmed);

            if( in_array($word_trimmed, $sql_reserved_all) && !in_array($word_trimmed, $sql_skip_reserved_words) )
            {
                if(in_array($prev_word, $sql_reserved_all))
                {
                    $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
                }
                else
                {
                    $sql_formatted .= '<br/>&nbsp;';
                    $sql_formatted .= '<b>'.strtoupper(trim($word)).'</b>'.'&nbsp;';
                }

                $prev_word = $word_trimmed;
                $word = "";
            }
            else
            {
                $sql_formatted .= trim($word).'&nbsp;';

                $prev_word = $word_trimmed;
                $word = "";
            }
        }
    }

    $sql_formatted .= trim($word);

    echo '<pre    >'.$sql_formatted.'</pre>';
}
