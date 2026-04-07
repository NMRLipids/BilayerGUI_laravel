function parsePositiveInt(value, fallback) {
    var n = parseInt(value, 10);
    return Number.isFinite(n) && n > 0 ? n : fallback;
}

function getDoiPreviewConfig() {
    var concurrencyMeta = document.querySelector('meta[name="doi-concurrent-fetches"]');
    var retriesMeta = document.querySelector('meta[name="doi-max-retries"]');

    return {
        concurrency: parsePositiveInt(concurrencyMeta && concurrencyMeta.content, 10),
        maxRetries: parsePositiveInt(retriesMeta && retriesMeta.content, 3),
    };
}

function initDoiPreview() {
    var doiLinks = Array.from(document.querySelectorAll('a.doi-link[data-doi]'));
    if (!doiLinks.length) return;

    var config = getDoiPreviewConfig();
    var popup = document.createElement('div');
    popup.className = 'doi-preview';
    popup.innerHTML = '<div class="doi-preview-loading">Loading...</div>';
    document.body.appendChild(popup);

    var hideTimer = null;
    var metaCache = {};
    var doiQueue = [];

    function forEachDoiLink(doi, cb) {
        doiLinks.forEach(function (link) {
            if (link.getAttribute('data-doi') === doi) cb(link);
        });
    }

    doiLinks.forEach(function (link) {
        var doi = link.getAttribute('data-doi');
        if (!doi || metaCache[doi]) return;
        metaCache[doi] = { loading: true };
        doiQueue.push(doi);
    });

    function delay(ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    }

    function disableDoiLinks(doi) {
        var meta = metaCache[doi];
        var tip = 'DOI could not be resolved';
        if (meta && meta.debug) tip += ' - ' + meta.debug.split('\n')[0];

        forEachDoiLink(doi, function (link) {
            link.removeAttribute('href');
            link.style.color = '#999';
            link.style.textDecoration = 'none';
            link.style.cursor = 'default';
            link.title = tip;
        });
    }

    function fetchOneDoi(doi, attempt) {
        attempt = attempt || 0;

        return fetch('https://doi.org/' + encodeURIComponent(doi), {
            headers: { Accept: 'application/citeproc+json' },
        })
            .then(function (response) {
                if (!response.ok) {
                    return response.text().then(function (body) {
                        if (attempt < config.maxRetries && (response.status === 429 || response.status >= 500)) {
                            return delay(1000 * Math.pow(2, attempt)).then(function () {
                                return fetchOneDoi(doi, attempt + 1);
                            });
                        }

                        metaCache[doi] = {
                            title: doi,
                            failed: true,
                            debug:
                                'HTTP ' +
                                response.status +
                                ' ' +
                                response.statusText +
                                '\n' +
                                body.substring(0, 200),
                        };
                        disableDoiLinks(doi);
                    });
                }

                var contentType = response.headers.get('content-type') || '';
                if (contentType.indexOf('json') === -1) {
                    metaCache[doi] = {
                        title: doi,
                        failed: true,
                        debug:
                            'HTTP ' +
                            response.status +
                            ' - unexpected content-type: ' +
                            contentType.split(';')[0],
                    };
                    disableDoiLinks(doi);
                    return;
                }

                return response.json().then(function (data) {
                    var authors = '';
                    if (data.author && data.author.length) {
                        authors = data.author
                            .slice(0, 4)
                            .map(function (author) {
                                return (author.family || '') + (author.given ? ', ' + author.given : '');
                            })
                            .join('; ');
                        if (data.author.length > 4) authors += ' et al.';
                    }

                    var year = '';
                    if (data.issued && data.issued['date-parts'] && data.issued['date-parts'][0]) {
                        year = data.issued['date-parts'][0][0] || '';
                    }

                    metaCache[doi] = {
                        title: data.title || doi,
                        authors: authors,
                        year: year,
                        type: data.type || '',
                        publisher: data.publisher || '',
                        container: data['container-title'] || '',
                    };
                });
            })
            .catch(function (err) {
                if (metaCache[doi] && metaCache[doi].failed) return;

                if (attempt < config.maxRetries) {
                    return delay(500).then(function () {
                        return fetchOneDoi(doi, attempt + 1);
                    });
                }

                metaCache[doi] = {
                    title: doi,
                    failed: true,
                    debug: 'Network error: ' + (err && err.message ? err.message : String(err)),
                };
                disableDoiLinks(doi);
            });
    }

    var doiIndex = 0;
    function runDoiWorker() {
        if (doiIndex >= doiQueue.length) return Promise.resolve();
        var doi = doiQueue[doiIndex++];
        return fetchOneDoi(doi).then(runDoiWorker);
    }

    for (var i = 0; i < Math.min(config.concurrency, doiQueue.length); i += 1) {
        runDoiWorker();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderCard(meta) {
        if (!meta || meta.loading) return '<div class="doi-preview-loading">Loading metadata...</div>';
        if (meta.failed) {
            var msg = '<div class="doi-preview-loading">Preview not available';
            if (meta.debug) {
                msg += '<br><small style="color:#c66;white-space:pre-wrap">' + escapeHtml(meta.debug) + '</small>';
            }
            msg += '</div>';
            return msg;
        }

        var html = '<div class="doi-preview-header">' + escapeHtml(meta.title) + '</div>';
        html += '<div class="doi-preview-body">';
        if (meta.authors) {
            html +=
                '<div class="doi-meta-row"><span class="doi-meta-label">Authors:</span> <span class="doi-meta-value">' +
                escapeHtml(meta.authors) +
                '</span></div>';
        }
        if (meta.container) {
            html +=
                '<div class="doi-meta-row"><span class="doi-meta-label">Journal:</span> <span class="doi-meta-value">' +
                escapeHtml(meta.container) +
                '</span></div>';
        }
        if (meta.publisher) {
            html +=
                '<div class="doi-meta-row"><span class="doi-meta-label">Publisher:</span> <span class="doi-meta-value">' +
                escapeHtml(meta.publisher) +
                '</span></div>';
        }
        if (meta.year) {
            html +=
                '<div class="doi-meta-row"><span class="doi-meta-label">Year:</span> <span class="doi-meta-value">' +
                escapeHtml(String(meta.year)) +
                '</span></div>';
        }
        if (meta.type) {
            html +=
                '<div class="doi-meta-row"><span class="doi-meta-label">Type:</span> <span class="doi-meta-value">' +
                escapeHtml(meta.type) +
                '</span></div>';
        }
        html += '</div>';
        return html;
    }

    document.addEventListener('mouseover', function (event) {
        var link = event.target.closest('a.doi-link[data-doi]');
        if (!link) return;

        clearTimeout(hideTimer);
        var doi = link.getAttribute('data-doi');
        popup.innerHTML = renderCard(metaCache[doi]);

        var rect = link.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 6;
        var left = rect.left + window.scrollX;
        if (left + 380 > window.innerWidth) left = window.innerWidth - 390;
        if (left < 5) left = 5;

        popup.style.top = top + 'px';
        popup.style.left = left + 'px';
        popup.style.display = 'block';
    });

    document.addEventListener('mouseout', function (event) {
        var link = event.target.closest('a.doi-link[data-doi]');
        if (!link) return;
        hideTimer = setTimeout(function () {
            popup.style.display = 'none';
        }, 300);
    });

    popup.addEventListener('mouseover', function () {
        clearTimeout(hideTimer);
    });
    popup.addEventListener('mouseout', function () {
        hideTimer = setTimeout(function () {
            popup.style.display = 'none';
        }, 300);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDoiPreview);
} else {
    initDoiPreview();
}
