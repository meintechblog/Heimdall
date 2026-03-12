<ul class="livestats">
    <li>
        <span class="title">Guests</span>
        <strong>{!! $guest_running !!}/{!! $guest_total !!}</strong>
    </li>
    <li>
        <span class="title">CPU</span>
        <strong>{!! round($cpu_percent, 1) !!}%</strong>
    </li>
    <li>
        <span class="title">RAM</span>
        <strong>{!! round($memory_percent, 1) !!}%</strong>
    </li>
</ul>
