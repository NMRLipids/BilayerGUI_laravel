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
<!-- SimpleLightbox plugin JS-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.js"></script>

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

    // Fetch metadata for all DOI links
    document.querySelectorAll('a.doi-link[data-doi]').forEach(function (link) {
        var doi = link.getAttribute('data-doi');
        if (!doi || metaCache[doi]) return;
        metaCache[doi] = { loading: true };
        fetch('https://doi.org/' + encodeURIComponent(doi), {
            headers: { 'Accept': 'application/citeproc+json' }
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
        .then(function (data) {
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
        })
        .catch(function () {
            metaCache[doi] = { title: doi, failed: true };
        });
    });

    function renderCard(meta) {
        if (!meta || meta.loading) return '<div class="doi-preview-loading">Loading metadata...</div>';
        if (meta.failed) return '<div class="doi-preview-loading">Preview not available</div>';
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
<!-- Core theme JS
<script src="storage/js/scripts.js"></script>
-->
<!--
<script type="text/javascript" src="{{ asset('storage/js/multislider.js') }}"></script>
-->



</body>

</html>
