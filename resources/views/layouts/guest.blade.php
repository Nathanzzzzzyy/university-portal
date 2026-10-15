<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal') | University of the Cordilleras</title>
    <link rel="icon" href="{{ asset('images/crest.png') }}">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    @if (config('recaptcha.enabled'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</head>
<body>
    {{-- LIVE campus background: slow zoom/pan, drifting clouds, sun glow, falling pine leaves --}}
    <div class="live-bg" aria-hidden="true">
        <div class="photo"></div>
        <div class="tint"></div>
        <div class="clouds"></div>
        <div class="sun"></div>
        <div id="leaves"></div>
    </div>

    <main class="auth-wrap">
        <section class="auth-brand">
            <img src="{{ asset('images/crest.png') }}" alt="University of the Cordilleras crest">
           <h1>University of the <span class="nowrap">Cordilleras Pangasinan</span></h1>
            <p>Philippines &middot; Est. 1946</p>
            @yield('brand')
        </section>

        <section class="auth-card">
            @if (session('status'))
                <div class="alert ok">{{ session('status') }}</div>
            @endif
            @yield('content')
        </section>
    </main>

    <script>
        // spawn drifting leaves over the live background
        (function () {
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const box = document.getElementById('leaves');
            for (let i = 0; i < 14; i++) {
                const l = document.createElement('i');
                l.className = 'leaf';
                l.style.left = Math.random() * 100 + 'vw';
                l.style.animationDuration = 12 + Math.random() * 18 + 's';
                l.style.animationDelay = -Math.random() * 20 + 's';
                l.style.transform = 'scale(' + (0.6 + Math.random()) + ')';
                box.appendChild(l);
            }
        })();
    </script>
<script>
    document.querySelectorAll('input[type="password"]').forEach((input, index) => {
        input.id ||= 'password-' + index;
        input.autocomplete = location.pathname === '/register' ? 'new-password' : 'current-password';
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input); wrapper.appendChild(input);
        input.style.paddingRight = '48px';
        const button = document.createElement('button');
        button.type = 'button'; button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-controls', input.id); button.setAttribute('aria-pressed', 'false');
        button.style.cssText = 'position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;padding:6px;color:#185c37';
        button.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
        button.onclick = () => {
            const visible = input.type === 'password'; input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(visible));
        };
        wrapper.appendChild(button);
    });
</script>
</body>
</html>