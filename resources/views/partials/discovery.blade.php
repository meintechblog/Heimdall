@if($enable_auth_admin_controls && Route::is('dash'))
    <section
        id="discovery-hub"
        class="discovery-hub"
        data-summary-url="{{ route('discoveries.summary') }}"
        data-progress-url="{{ route('discoveries.progress') }}"
        data-candidates-url="{{ route('discoveries.candidates') }}"
        data-add-url="{{ route('discoveries.store') }}"
        data-refresh-seconds="{{ config('app.discovery.summary_refresh_seconds', 300) }}"
    >
        <button type="button" id="discovery-toggle" class="discovery-toggle is-hidden" aria-expanded="false" aria-label="Neue Services anzeigen">
            <span class="discovery-toggle-count" data-role="count"></span>
            <span class="discovery-toggle-icon" data-role="icon">+</span>
        </button>
    </section>
@endif
