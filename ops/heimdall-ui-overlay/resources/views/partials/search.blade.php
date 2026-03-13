@php($searchForm = App\Search::form())

@if($searchForm !== '' || ($enable_auth_admin_controls && Route::is('dash')))
    <section class="search-discovery-shell">
        <div class="search-discovery-row">
            <div class="search-discovery-search">
                {!! $searchForm !!}
            </div>
            @include('partials.discovery')
        </div>
        <div class="discovery-panel is-hidden" data-role="panel">
            <div class="discovery-candidate-list" data-role="candidates"></div>
            <div class="discovery-panel-state is-hidden" data-role="state"></div>
        </div>
    </section>
@endif
