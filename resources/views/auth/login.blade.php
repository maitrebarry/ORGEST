<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d0d0d">
    <link rel="icon" href="{{ asset('assets/images/icon-32.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/icon-180.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="ORGEST">
    <title>Connexion - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        :root {
            --theme-accent: #c9972c;
            --theme-accent-2: #f4d35e;
            --theme-dark: #0d0d0d;
            --theme-soft: rgba(201, 151, 44, .12);
            --theme-ring: rgba(201, 151, 44, .28);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #172033;
            overflow-x: hidden;
        }

        .bg-aurora {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1206 35%, #3d2c0a 55%, #0a0a0a 100%);
            background-size: 400% 400%;
            animation: gradientShift 18s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .bg-slideshow { position: fixed; inset: 0; z-index: 0; overflow: hidden; }

        .bg-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.4s ease-in-out;
            transform: scale(1.06);
        }

        .bg-slide.active { opacity: 1; animation: kenburns 9s ease-out forwards; }

        @keyframes kenburns {
            from { transform: scale(1.06); }
            to { transform: scale(1.16); }
        }

        .bg-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                linear-gradient(90deg, rgba(8, 6, 2, .62) 0%, rgba(8, 6, 2, .34) 42%, rgba(8, 6, 2, .84) 100%),
                linear-gradient(180deg, rgba(8, 6, 2, .18) 0%, rgba(8, 6, 2, .32) 65%, rgba(13, 10, 3, .90) 100%);
        }

        .bg-caption-wrap {
            position: fixed;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            padding: 0 6vw;
            pointer-events: none;
        }

        .bg-caption {
            display: none;
            max-width: 560px;
            color: #fff;
        }

        .bg-caption.active { display: block; animation: captionIn .8s ease; }

        @keyframes captionIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bg-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .25);
            color: #fff;
            font-size: .78rem;
            font-weight: 700;
            padding: .3rem .8rem;
            border-radius: 999px;
            margin-bottom: 1rem;
        }

        .bg-title {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            font-weight: 800;
            margin: 0 0 .6rem;
        }

        .bg-text {
            font-size: clamp(.85rem, 1vw, 1rem);
            color: rgba(255, 255, 255, .8);
            margin: 0;
        }

        .bg-dots {
            position: fixed;
            left: 6vw;
            bottom: 90px;
            z-index: 2;
            display: flex;
            gap: .5rem;
        }

        .bg-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .35);
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .bg-dot.active { background: #fff; width: 22px; border-radius: 4px; transition: all .3s; }

        .module-wave {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            z-index: 2;
            pointer-events: none; /* bandeau décoratif : ne bloque pas les puces du carrousel */
        }

        .module-wave svg { display: block; width: 100%; height: auto; }

        .module-wave-fill { fill: var(--theme-dark); }

        .module-list {
            background: var(--theme-dark);
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: .5rem 1rem;
            padding: .75rem 4vw 1rem;
        }

        .module-node {
            display: flex;
            align-items: center;
            gap: .4rem;
            color: rgba(255, 255, 255, .85);
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
        }

        .module-node i { font-size: 1rem; color: var(--theme-accent-2); }
        .module-node span { overflow: hidden; text-overflow: ellipsis; }

        .login-page {
            position: relative;
            z-index: 3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 2rem 6vw 8rem;
            pointer-events: none; /* laisse passer les clics vers les puces du carrousel */
        }

        .bg-dots { pointer-events: auto; }

        .login-card {
            width: 100%;
            max-width: 420px;
            pointer-events: auto; /* la carte reste pleinement interactive */
            background: rgba(255, 255, 255, .93);
            backdrop-filter: blur(18px) saturate(1.4);
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 18px;
            box-shadow: 0 32px 80px rgba(10, 18, 35, .28), 0 0 0 1px rgba(255, 255, 255, .18) inset;
            overflow: hidden;
        }

        .login-card-accent {
            height: 4px;
            background: linear-gradient(90deg, var(--theme-accent), var(--theme-accent-2) 60%, var(--theme-accent));
        }

        .login-card-body { padding: 2rem 2rem 1.5rem; }

        .brand-area { text-align: center; margin-bottom: 1.25rem; }

        .brand-logo { display: inline-flex; margin-bottom: .5rem; }

        .brand-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -.02em;
        }

        .brand-title-a { color: var(--theme-accent); }
        .brand-title-b { color: var(--theme-accent-2); }

        .brand-subtitle {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: #64748b;
            margin: .2rem 0 0;
        }

        .form-divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #94a3b8;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 1.25rem 0 1rem;
        }

        .form-divider::before, .form-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .login-card .form-label {
            font-size: .84rem;
            font-weight: 700;
            color: #334155;
        }

        .login-card .input-group-text {
            background: #f1f5f9;
            border-color: #dfe5ee;
            color: #64748b;
        }

        .login-card .form-control {
            border-color: #dfe5ee;
            padding-top: .6rem;
            padding-bottom: .6rem;
        }

        .login-card .form-control:focus {
            border-color: var(--theme-accent);
            box-shadow: 0 0 0 .2rem var(--theme-ring);
        }

        .password-toggle {
            border-color: #dfe5ee;
            background: #fff;
            color: #64748b;
        }

        .login-button {
            background: linear-gradient(135deg, var(--theme-accent) 0%, var(--theme-dark) 100%);
            border: none;
            color: #fff;
            font-weight: 800;
            font-size: .95rem;
            padding: .7rem;
            border-radius: .5rem;
            box-shadow: 0 8px 24px var(--theme-ring);
        }

        .login-button:hover { color: #fff; opacity: .95; }

        .login-footer {
            text-align: center;
            font-size: .75rem;
            color: #94a3b8;
            margin-top: 1.25rem;
        }

        @media (max-width: 900px) {
            .bg-caption-wrap { display: none; }
            .bg-dots { display: none; }
        }

        @media (max-width: 600px) {
            .login-page { justify-content: center; padding: 1.5rem 1.25rem 9rem; }
            .module-list { grid-template-columns: repeat(3, 1fr); }
            .module-node span { display: none; }
            .brand-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="bg-aurora"></div>

    <div class="bg-slideshow">
        @foreach ($slides as $index => $slide)
            <div class="bg-slide @if ($index === 0) active @endif" data-index="{{ $index }}"
                 style="background-image: url('{{ asset('assets/images/login/slide-'.($index + 1).'.png') }}');"></div>
        @endforeach
    </div>

    <div class="bg-overlay"></div>

    <div class="bg-caption-wrap">
        @foreach ($slides as $index => $slide)
            <div class="bg-caption @if ($index === 0) active @endif" data-index="{{ $index }}">
                <span class="bg-badge"><i class="bi {{ $slide['icon'] }}"></i> {{ $slide['badge'] }}</span>
                <h2 class="bg-title">{{ $slide['title'] }}</h2>
                <p class="bg-text">{{ $slide['text'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-dots" id="bgDots">
        @foreach ($slides as $index => $slide)
            <button type="button" class="bg-dot @if ($index === 0) active @endif" data-index="{{ $index }}"></button>
        @endforeach
    </div>

    <div class="module-wave">
        <svg viewBox="0 0 1200 60" preserveAspectRatio="none">
            <path class="module-wave-fill" d="M0,30 C150,60 350,0 600,20 C850,40 1050,0 1200,25 L1200,60 L0,60 Z"></path>
        </svg>
        <div class="module-list">
            @foreach ($modules as $module)
                <div class="module-node">
                    <i class="bi {{ $module['icon'] }}"></i>
                    <span>{{ $module['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <main class="login-page">
        <section class="login-card">
            <div class="login-card-accent"></div>
            <div class="login-card-body">
                <div class="brand-area">
                    <div class="brand-logo">
                        <img src="{{ asset('assets/images/logo-orgest.png') }}" alt="ORGEST"
                             style="height: 100px; width: 100px; border-radius: 22px; object-fit: cover; box-shadow: 0 8px 22px rgba(10, 18, 35, .22);">
                    </div>
                    <h1 class="brand-title">
                        <span class="brand-title-b">OR</span><span class="brand-title-a">GEST</span>
                    </h1>
                    <p class="brand-subtitle">GESTION ACHAT &amp; VENTE D'OR</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="form-divider"><span>Connexion</span></div>

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="phone" class="form-label">Numéro de téléphone</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="70000000" autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            <button type="button" class="btn password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Se souvenir de moi</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn login-button" id="loginButton">
                            <span class="login-button-label"><i class="bi bi-box-arrow-in-right"></i> Connexion</span>
                        </button>
                    </div>
                </form>

                <div class="login-footer">&copy; {{ date('Y') }} ORGEST — Computer Service BARRY</div>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginButton');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Connexion en cours…';
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });

        const captions = document.querySelectorAll('.bg-caption');
        const dots = document.querySelectorAll('.bg-dot');
        const slides = document.querySelectorAll('.bg-slide');
        let current = 0;

        function activate(index) {
            captions[current].classList.remove('active');
            dots[current].classList.remove('active');
            if (slides.length) slides[current % slides.length].classList.remove('active');

            current = index;

            captions[current].classList.add('active');
            dots[current].classList.add('active');
            if (slides.length) slides[current % slides.length].classList.add('active');
        }

        setInterval(function () {
            activate((current + 1) % captions.length);
        }, 6000);

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                activate(parseInt(this.dataset.index, 10));
            });
        });
    </script>
</body>
</html>
