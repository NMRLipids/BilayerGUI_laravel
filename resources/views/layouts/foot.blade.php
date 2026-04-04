<footer class="text-center text-white navbar bg-primary">
    <!-- fixed-bottom-->
    <!-- Grid container -->
    <div class=" p-4 w-100">
        <!-- Section: Images -->
        <section>

            <div class="row justify-content-center align-items-center ">
                <span> Copyright &copy;{{ date('Y') }} - FAIRMD Lipids (Formerly known as NMRlipids) - Universidade de Santiago de Compostela, Universitetet i Bergen </span>
        
            </div>

        </section>
        <!-- Section: Images -->
    </div>
    <!-- Grid container -->

</footer>
<!-- Bootstrap core JS (fallback if Vite dev server is unavailable) -->
<script>
if (typeof bootstrap === 'undefined') {
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
    s.integrity = 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz';
    s.crossOrigin = 'anonymous';
    document.head.appendChild(s);
}
</script>

<!-- Mobile navbar fallback toggle (reliable open/close on repeated taps) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var togglers = document.querySelectorAll('.navbar-toggler[data-nav-target]');
    togglers.forEach(function (btn) {
        var targetSelector = btn.getAttribute('data-nav-target');
        var target = targetSelector ? document.querySelector(targetSelector) : null;
        if (!target) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var isOpen = target.classList.contains('show');
            target.classList.toggle('show', !isOpen);
            btn.setAttribute('aria-expanded', String(!isOpen));
        });

        target.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 991.98px)').matches) {
                    target.classList.remove('show');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        });
    });
});
</script>

<!-- SimpleLightbox plugin JS-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.js"></script>

<!-- High-contrast toggle + OS preference detection -->
<script>
(function () {
    var root = document.documentElement;
    var stored = localStorage.getItem('high-contrast');

    function applyContrast(on) {
        if (on) {
            root.classList.add('high-contrast');
            root.classList.remove('no-high-contrast');
        } else {
            root.classList.remove('high-contrast');
            root.classList.add('no-high-contrast');
        }
    }

    // Determine initial state: localStorage overrides OS preference
    if (stored === 'on') {
        applyContrast(true);
    } else if (stored === 'off') {
        applyContrast(false);
    } else {
        // No user choice yet — follow OS preference
        var osHigh = window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches;
        if (osHigh) {
            applyContrast(true);
        }
        // else: default (no class), media query in CSS handles auto
    }

    // Listen for OS preference changes (live)
    if (window.matchMedia) {
        window.matchMedia('(prefers-contrast: high)').addEventListener('change', function (e) {
            if (!localStorage.getItem('high-contrast')) {
                applyContrast(e.matches);
            }
        });
    }

    // Toggle button click
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.contrast-toggle');
        if (!btn) return;
        var isOn = root.classList.contains('high-contrast');
        applyContrast(!isOn);
        localStorage.setItem('high-contrast', isOn ? 'off' : 'on');
    });
})();
</script>

<!-- Async DOI hover preview card for .doi-link elements -->
<style>
.doi-preview {
    display: none;
    position: absolute;
    z-index: 9999;
    background: #fff;
    border: 1px solid #bbb;
    border-radius: 8px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.3);
    width: 380px;
    max-width: 90vw;
    overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #333;
}
.doi-preview-header {
    padding: 10px 14px;
    background: #2a6496;
    color: #fff;
    font-size: 0.95em;
    font-weight: 600;
    line-height: 1.3;
}
.doi-preview-body {
    padding: 10px 14px;
    font-size: 0.85em;
    line-height: 1.5;
}
.doi-preview-body .doi-meta-row {
    margin-bottom: 4px;
}
.doi-preview-body .doi-meta-label {
    font-weight: 600;
    color: #555;
    min-width: 70px;
    display: inline-block;
}
.doi-preview-body .doi-meta-value {
    color: #222;
}
.doi-preview-footer {
    padding: 6px 14px 10px;
    font-size: 0.8em;
    color: #888;
    border-top: 1px solid #eee;
}
.doi-preview-loading {
    padding: 20px 14px;
    text-align: center;
    color: #999;
    font-size: 0.85em;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var popup = document.createElement('div');
    popup.className = 'doi-preview';
    popup.innerHTML = '<div class="doi-preview-loading">Loading...</div>';
    document.body.appendChild(popup);
    var hideTimer = null;
    var metaCache = {};

    // Fetch metadata for all DOI links (sequentially to avoid connection/rate limits)
    var doiLinks = Array.from(document.querySelectorAll('a.doi-link[data-doi]'));
    var doiQueue = [];
    doiLinks.forEach(function (link) {
        var doi = link.getAttribute('data-doi');
        if (!doi || metaCache[doi]) return;
        metaCache[doi] = { loading: true };
        if (doiQueue.indexOf(doi) === -1) doiQueue.push(doi);
    });

    var DOI_CONCURRENCY = {{ config('app.doi_concurrent_fetches', 10) }};
    var DOI_MAX_RETRIES = {{ config('app.doi_max_retries', 3) }};

    function delay(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

    function fetchOneDOI(doi, attempt) {
        attempt = attempt || 0;
        return fetch('https://doi.org/' + encodeURIComponent(doi), {
            headers: { 'Accept': 'application/citeproc+json' }
        })
        .then(function (r) {
            if (!r.ok) {
                return r.text().then(function (body) {
                    if (attempt < DOI_MAX_RETRIES && (r.status === 429 || r.status >= 500)) {
                        return delay(1000 * Math.pow(2, attempt)).then(function () {
                            return fetchOneDOI(doi, attempt + 1);
                        });
                    }
                    metaCache[doi] = { title: doi, failed: true, debug: 'HTTP ' + r.status + ' ' + r.statusText + '\n' + body.substring(0, 200) };
                    disableDoiLinks(doi);
                });
            }
            var ct = (r.headers.get('content-type') || '');
            if (ct.indexOf('json') === -1) {
                metaCache[doi] = { title: doi, failed: true, debug: 'HTTP ' + r.status + ' — unexpected content-type: ' + ct.split(';')[0] };
                disableDoiLinks(doi);
                return;
            }
            return r.json().then(function (data) {
                var authors = '';
                if (data.author && data.author.length) {
                    authors = data.author.slice(0, 4).map(function (a) {
                        return (a.family || '') + (a.given ? ', ' + a.given : '');
                    }).join('; ');
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
                    container: data['container-title'] || ''
                };
            });
        })
        .catch(function (err) {
            // Already handled (HTTP error or content-type mismatch) — skip
            if (metaCache[doi] && metaCache[doi].failed) return;
            // Network error — retry with short delay (rate-limiting drops connections)
            if (attempt < DOI_MAX_RETRIES) {
                return delay(500).then(function () {
                    return fetchOneDOI(doi, attempt + 1);
                });
            }
            metaCache[doi] = { title: doi, failed: true, debug: 'Network error: ' + (err && err.message ? err.message : String(err)) };
            disableDoiLinks(doi);
        });
    }

    // Process DOI queue with limited concurrency
    var doiIndex = 0;
    function runDoiWorker() {
        if (doiIndex >= doiQueue.length) return Promise.resolve();
        var doi = doiQueue[doiIndex++];
        return fetchOneDOI(doi).then(runDoiWorker);
    }
    var workers = [];
    for (var w = 0; w < Math.min(DOI_CONCURRENCY, doiQueue.length); w++) {
        workers.push(runDoiWorker());
    }

    function disableDoiLinks(doi) {
        var meta = metaCache[doi];
        var tip = 'DOI could not be resolved';
        if (meta && meta.debug) tip += ' — ' + meta.debug.split('\n')[0];
        document.querySelectorAll('a.doi-link[data-doi="' + doi + '"]').forEach(function (link) {
            link.removeAttribute('href');
            link.style.color = '#999';
            link.style.textDecoration = 'none';
            link.style.cursor = 'default';
            link.title = tip;
        });
    }

    function renderCard(meta) {
        if (!meta || meta.loading) return '<div class="doi-preview-loading">Loading metadata...</div>';
        if (meta.failed) {
            var msg = '<div class="doi-preview-loading">Preview not available';
            if (meta.debug) msg += '<br><small style="color:#c66;white-space:pre-wrap">' + escapeHtml(meta.debug) + '</small>';
            msg += '</div>';
            return msg;
        }
        var html = '<div class="doi-preview-header">' + escapeHtml(meta.title) + '</div>';
        html += '<div class="doi-preview-body">';
        if (meta.authors) html += '<div class="doi-meta-row"><span class="doi-meta-label">Authors:</span> <span class="doi-meta-value">' + escapeHtml(meta.authors) + '</span></div>';
        if (meta.container) html += '<div class="doi-meta-row"><span class="doi-meta-label">Journal:</span> <span class="doi-meta-value">' + escapeHtml(meta.container) + '</span></div>';
        if (meta.publisher) html += '<div class="doi-meta-row"><span class="doi-meta-label">Publisher:</span> <span class="doi-meta-value">' + escapeHtml(meta.publisher) + '</span></div>';
        if (meta.year) html += '<div class="doi-meta-row"><span class="doi-meta-label">Year:</span> <span class="doi-meta-value">' + escapeHtml(String(meta.year)) + '</span></div>';
        if (meta.type) html += '<div class="doi-meta-row"><span class="doi-meta-label">Type:</span> <span class="doi-meta-value">' + escapeHtml(meta.type) + '</span></div>';
        html += '</div>';
        return html;
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // Show on hover
    document.addEventListener('mouseover', function (e) {
        var link = e.target.closest('a.doi-link[data-doi]');
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

    document.addEventListener('mouseout', function (e) {
        var link = e.target.closest('a.doi-link[data-doi]');
        if (!link) return;
        hideTimer = setTimeout(function () { popup.style.display = 'none'; }, 300);
    });

    popup.addEventListener('mouseover', function () { clearTimeout(hideTimer); });
    popup.addEventListener('mouseout', function () {
        hideTimer = setTimeout(function () { popup.style.display = 'none'; }, 300);
    });
});
</script>
<!-- Navbar shrink on scroll (adds backdrop blur) -->
<script>
(function () {
    var nav = document.getElementById('mainNav');
    if (!nav) return;
    function onScroll() {
        if (window.scrollY > 0) {
            nav.classList.add('navbar-shrink');
        } else {
            nav.classList.remove('navbar-shrink');
        }
    }
    onScroll();
    document.addEventListener('scroll', onScroll, { passive: true });
})();
</script>
<!-- Core theme JS
<script src="storage/js/scripts.js"></script>
-->
<!--
<script type="text/javascript" src="{{ asset('storage/js/multislider.js') }}"></script>
-->



</body>

</html>
