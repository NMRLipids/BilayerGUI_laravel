<footer class="text-center text-white navbar bg-primary">
    <!-- fixed-bottom-->
    <!-- Grid container -->
    <div class=" p-4 w-100">
        <!-- Funding -->
        <section class="mb-3">
            <div class="row justify-content-center mb-2">
                <div class="col-auto d-flex align-items-center justify-content-center gap-3">
                    <img src="{{ asset('storage/images/EN_Co-fundedbytheEU_RGB_WHITE.svg') }}" alt="Co-funded by the EU" style="max-height:64px; width:auto; display:block;">
                    <img src="{{ asset('storage/images/TMS_eng_sh.svg') }}" alt="Co-funded by the Trond Mohn Foundation" style="max-height:75px; width:auto; display:block;">
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-auto text-center">
                    <p class="mb-0" style="max-width:780px;">
                        The FAIRMD project has been co-funded from the European Commission’s Horizon Europe Research and Innovation programme through the
                        <a href="https://oscars-project.eu/" target="_blank" rel="noopener" class="text-white text-decoration-underline">OSCARS</a> project Open Call under grant agreement No.
                        <a href="https://cordis.europa.eu/project/id/101129751" target="_blank" rel="noopener" class="text-white text-decoration-underline">101129751</a>,
                        M.S.M. is supported by the
                        <a href="https://mohnfoundation.no/en/" target="_blank" rel="noopener" class="text-white text-decoration-underline">Trond Mohn Foundation</a> (BFS2017TMT01)
                    </p>
                </div>
            </div>
        </section>
        <!-- Section: Copyright -->
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

    // Initialize Bootstrap tooltips when available (works with bundled or fallback Bootstrap JS)
    (function initTooltips(retriesLeft) {
        if (!window.bootstrap || !bootstrap.Tooltip) {
            if (retriesLeft > 0) setTimeout(function () { initTooltips(retriesLeft - 1); }, 120);
            return;
        }
        var tooltipEls = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });
    })(20);
});
</script>


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