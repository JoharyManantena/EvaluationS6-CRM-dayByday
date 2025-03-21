<!-- _navbar.blade.php -->

<!-- Desktops NAV -->
<button type="button" class="navbar-toggle menu-txt-toggle" style="">
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
</button>

<!-- Mobile NAV -->
<button type="button" id="mobile-toggle" class="mobile-toggle mobile-nav" data-toggle="offcanvas"
        data-target="#myNavmenu">
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
    <span class="icon-bar"></span>
</button>

<div class="navbar navbar-default navbar-top">

    <div class="navbar-icons__wrapper">
        <div id="nav-toggle col-sm-6">
            <search></search>
        </div>
        
        @if(Entrust::hasRole('administrator') || Entrust::hasRole('owner'))
            <div id="nav-toggle col-sm-4">
                <a href="{{ route('settings.index') }}" style="text-decoration: none;">
                    <span class="top-bar-toggler">
                        <i class="flaticon-gear"></i>
                    </span>
                </a>
            </div>
        @endif

        <!-- BUTTON "INITIALIZE DATA" -->
        @if(Entrust::hasRole('administrator') || Entrust::hasRole('owner'))
            <!-- Initialize Data Button -->
            <div id="nav-toggle col-sm-4">
                <button id="initialize-data-btn" class="btn btn-warning" style="margin: 5px;">
                    <i class="fa fa-refresh"></i> {{ __('Initialize Data') }}
                </button>
            </div>
        @endif

        @include('navigation.topbar.user-profile')
        
        <div id="nav-toggle col-sm-2">
            <a id="grid-action" role="button" data-toggle="dropdown">
                <span class="top-bar-toggler">
                    <i class="flaticon-grid"></i>
                </span>
            </a>
        </div>
    </div>

    @include('partials.action-panel._panel')
    
</div>

<!-- Display success message -->
@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            alert("{{ session('success') }}");
        });
    </script>
@endif

<!-- POPUP (MODAL) POUR CONFIRMATION -->
<div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">{{ __('Confirm the action.') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>{{ __('Do you really want to initialize the data? This action is irreversible!') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Annuler') }}</button>
                <form id="reset-form" action="{{ route('reset.database') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">{{ __('Initialiser') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT POUR OUVRIR LA POPUP ET CONFIRMER L'INITIALISATION -->
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("initialize-data-btn").addEventListener("click", function () {
            // Afficher la popup de confirmation
            $('#confirmationModal').modal('show');
        });

        // Handle form submission on confirmation
        document.getElementById("reset-form").addEventListener("submit", function () {
            // Add additional AJAX or confirmation logic if needed
        });
    });
</script>
@endpush

<!-- STYLE CSS -->
<style>
    .btn-warning {
        background-color: #ff9800; /* Orange */
        border: none;
        padding: 8px 12px;
        font-size: 14px;
        cursor: pointer;
    }

    .btn-warning:hover {
        background-color: #e68900;
    }

    /* Style pour la popup/modal */
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-footer {
        background-color: #f8f9fa;
    }

    .modal-title {
        font-weight: bold;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
        padding: 10px;
        margin-top: 10px;
        border-radius: 4px;
    }
</style>