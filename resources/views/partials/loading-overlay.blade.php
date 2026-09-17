<style>
    .page-loader{position:fixed;inset:0;background:rgba(255,255,255,.72);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:9999;opacity:0;visibility:hidden;transition:opacity .15s ease}
    .page-loader.is-active{opacity:1;visibility:visible}
    .page-loader-box{display:flex;flex-direction:column;align-items:center;gap:12px}
    .page-loader-spinner{width:44px;height:44px;border-radius:50%;border:4px solid var(--line,#dde5f0);border-top-color:var(--brand,#0057a8);animation:page-loader-spin .7s linear infinite}
    .page-loader-text{font-weight:800;color:var(--brand,#0057a8);font-size:13px;letter-spacing:.02em}
    @keyframes page-loader-spin{to{transform:rotate(360deg)}}
</style>

<div id="page-loader" class="page-loader" aria-hidden="true">
    <div class="page-loader-box">
        <span class="page-loader-spinner"></span>
        <span class="page-loader-text">Loading…</span>
    </div>
</div>

<script>
    (function () {
        var loader = document.getElementById('page-loader');
        if (! loader) return;

        var hideTimer;

        function showLoader() {
            loader.classList.add('is-active');
            loader.setAttribute('aria-hidden', 'false');
            // Safety net: a page that never finishes navigating (dropped
            // connection, blocked request) would otherwise leave the
            // overlay stuck forever.
            window.clearTimeout(hideTimer);
            hideTimer = window.setTimeout(hideLoader, 8000);
        }

        function hideLoader() {
            loader.classList.remove('is-active');
            loader.setAttribute('aria-hidden', 'true');
            window.clearTimeout(hideTimer);
        }

        window.PageLoader = { show: showLoader, hide: hideLoader };

        document.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            var link = event.target.closest('a[href]');
            if (! link || link.dataset.noLoader !== undefined) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;

            var href = link.getAttribute('href') || '';
            if (! href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (e) {
                return;
            }

            if (url.origin !== window.location.origin) return;
            // A link to the same page that only changes the hash (in-page anchor) doesn't navigate.
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

            showLoader();
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (event.defaultPrevented) return;
            if (form.dataset.noLoader !== undefined) return;
            // AJAX forms (add-to-cart, etc.) manage their own loading state.
            if (form.hasAttribute('data-cart-add')) return;

            showLoader();
        });

        // Restores from the browser's back/forward cache arrive with the
        // page already rendered, so any loader left over from before must
        // be cleared instead of sitting on screen.
        window.addEventListener('pageshow', hideLoader);
    })();
</script>
