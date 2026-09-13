<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <span class="logo-icon logo-3d-mini">OG</span>
        </div>
        <div>
            <h4 class="logo-text logo-3d mb-0"><span class="w3d-w">OR</span><span class="w3d-n">GEST</span></h4>
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i></div>
    </div>

    <ul class="metismenu" id="menu">
        <li>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'mm-active' : '' }}">
                <div class="parent-icon"><i class='bx bx-home-circle'></i></div>
                <div class="menu-title">Tableau de bord</div>
            </a>
        </li>

        @can('clients.voir')
            <li>
                <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-group'></i></div>
                    <div class="menu-title">Clients</div>
                </a>
            </li>
        @endcan

        @can('achats.voir')
            <li>
                <a href="{{ route('achats.index') }}" class="{{ request()->routeIs('achats.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-coin-stack'></i></div>
                    <div class="menu-title">Achats d'or</div>
                </a>
            </li>
        @endcan

        @can('achats.voir')
            <li>
                <a href="{{ route('stock.index') }}" class="{{ request()->routeIs('stock.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-cube'></i></div>
                    <div class="menu-title">Stock</div>
                </a>
            </li>
        @endcan

        @can('ventes.voir')
            <li>
                <a href="{{ route('ventes.index') }}" class="{{ request()->routeIs('ventes.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-transfer-alt'></i></div>
                    <div class="menu-title">Ventes d'or</div>
                </a>
            </li>
        @endcan

        @can('audit.voir')
            <li>
                <a href="{{ route('audit.index') }}" class="{{ request()->routeIs('audit.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-list-check'></i></div>
                    <div class="menu-title">Audit</div>
                </a>
            </li>
        @endcan

        @can('fonds.voir')
            <li>
                <a href="{{ route('tresorerie.index') }}" class="{{ request()->routeIs('tresorerie.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-money-withdraw'></i></div>
                    <div class="menu-title">Trésorerie</div>
                </a>
            </li>
        @endcan

        @can('credits.voir')
            <li>
                <a href="{{ route('credits.index') }}" class="{{ request()->routeIs('credits.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-wallet'></i></div>
                    <div class="menu-title">Cahier de crédit</div>
                </a>
            </li>
        @endcan

        @can('bareme.voir')
            <li>
                <a href="{{ route('baremes.index') }}" class="{{ request()->routeIs('baremes.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-grid-alt'></i></div>
                    <div class="menu-title">Barème</div>
                </a>
            </li>
        @endcan

        @can('utilisateurs.voir')
            <li>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*', 'permissions.*', 'user-permissions.*', 'bureaux.*') ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='bx bx-cog bx-spin'></i></div>
                    <div class="menu-title">Configuration</div>
                </a>
            </li>
        @endcan
    </ul>
</div>
