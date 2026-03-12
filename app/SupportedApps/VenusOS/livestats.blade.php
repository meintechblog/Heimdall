<ul class="livestats">
    <li>
        <span class="title">PV</span>
        <strong>{!! $pv_value !!}</strong>
    </li>
    <li>
        <span class="title">Bat</span>
        <strong>{!! $battery_value !!}</strong>
    </li>
    <li>
        <span class="title">Grid</span>
        <strong class="venus-grid-value {!! $grid_css_class !!}">@if($grid_arrow !== '')<span class="venus-grid-arrow">{!! $grid_arrow !!}</span>@endif {!! $grid_value !!}</strong>
    </li>
</ul>
