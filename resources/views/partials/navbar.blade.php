<header>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>

            <div class="search-bar flex-grow-1">
                <div class="position-relative search-bar-box">
                    <input type="text" class="form-control search-control" placeholder="Rechercher...">
                    <span class="position-absolute top-50 search-show translate-middle-y"><i class='bx bx-search'></i></span>
                    <span class="position-absolute top-50 search-close translate-middle-y"><i class='bx bx-x'></i></span>
                </div>
            </div>

            <div class="top-menu ms-auto">
                <ul class="navbar-nav align-items-center">
                    @php
                        $journeeNavbar = auth()->user()->can('fonds.voir')
                            ? \App\Models\JourneeFinanciere::where('bureau_id', auth()->user()->bureau_id)->ouverte()->latest('date_ouverture')->first()
                            : null;
                        $alerteTresorerieNavbar = null;
                        if ($journeeNavbar?->estEnRupture()) {
                            $alerteTresorerieNavbar = [
                                'icone' => 'bx-error-circle', 'classe' => 'text-danger',
                                'texte' => "Fonds épuisé. Plus aucun paiement d'achat ne pourra être honoré tant qu'un approvisionnement n'aura pas été enregistré.",
                            ];
                        } elseif ($journeeNavbar?->soldeFaible()) {
                            $devise = auth()->user()->devise_symbole;
                            $alerteTresorerieNavbar = [
                                'icone' => 'bx-error', 'classe' => 'text-warning',
                                'texte' => 'Solde faible. Il ne reste que '.number_format($journeeNavbar->soldeDisponible(), 0, ',', ' ').' '.$devise.' disponible (moins de 10% des entrées du jour) — pensez à demander un approvisionnement pour éviter une rupture.',
                            ];
                        }
                    @endphp
                    <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class='bx bx-bell'></i>
                            @if ($alerteTresorerieNavbar)
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">Alerte</span>
                                </span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:;">
                                <div class="msg-header">
                                    <p class="msg-header-title">Notifications</p>
                                </div>
                            </a>
                            <div class="header-notifications-list">
                                @if ($alerteTresorerieNavbar)
                                    <a href="{{ route('tresorerie.index') }}" class="dropdown-item">
                                        <div class="d-flex align-items-start gap-2 py-1">
                                            <i class='bx {{ $alerteTresorerieNavbar['icone'] }} {{ $alerteTresorerieNavbar['classe'] }} fs-4'></i>
                                            <div class="text-wrap small">{{ $alerteTresorerieNavbar['texte'] }}</div>
                                        </div>
                                    </a>
                                @else
                                    <div class="dropdown-item text-center text-muted py-4">
                                        Aucune notification pour le moment
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="user-box dropdown">
                <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if (auth()->user()->photo_url)
                        <img src="{{ auth()->user()->photo_url }}" class="user-img" style="object-fit: cover;" alt="Photo de profil">
                    @else
                        <div class="user-img d-flex align-items-center justify-content-center bg-primary text-white fw-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="user-info ps-3">
                        <p class="user-name mb-0">{{ auth()->user()->name }}</p>
                        <p class="designattion mb-0">{{ ucfirst(str_replace('_', ' ', auth()->user()->roles->first()?->name ?? '')) }}</p>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bx bx-user"></i><span>Profil</span></a></li>
                    <li><div class="dropdown-divider mb-0"></div></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class='bx bx-log-out-circle'></i><span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </nav>
    </div>
</header>
