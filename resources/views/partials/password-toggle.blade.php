{{-- Adds a show/hide "eye" button to every password input on the page. --}}
<style>
    .pw-wrap{position:relative;display:block;width:100%;margin-top:6px}
    .pw-wrap input{width:100%;margin-top:0 !important;padding-right:46px !important}
    .pw-toggle{position:absolute;top:50%;right:6px;transform:translateY(-50%);width:34px;height:34px;border:0;border-radius:6px;background:transparent;color:#667085;cursor:pointer;display:grid;place-items:center;padding:0}
    .pw-toggle:hover{background:#eef5ff;color:#004b93}
    .pw-toggle:focus-visible{outline:2px solid #004b93;outline-offset:1px}
    .pw-toggle svg{width:20px;height:20px}
</style>
<script>
    (function () {
        var eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
        var eyeOff = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        function enhance(input) {
            if (input.dataset.pwToggle) return;
            input.dataset.pwToggle = '1';

            var wrap = document.createElement('span');
            wrap.className = 'pw-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pw-toggle';
            btn.innerHTML = eye;
            btn.setAttribute('aria-label', 'Show password');
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? eyeOff : eye;
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                input.focus();
            });
            wrap.appendChild(btn);
        }

        function run() {
            document.querySelectorAll('input[type="password"]').forEach(enhance);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
</script>
