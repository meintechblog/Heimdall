@php
    $aliases = $item->wledAliases();
    $preferredUrl = $item->wledPreferredUrl();
    $wledIdentity = $item->wledIdentity();
@endphp

<div class="input wled-network-config">
    <label>WLED Netzwerk</label>
    <small class="help">
        Bekannte Adressen fuer dieses WLED-Geraet. Die ausgewaehlte Adresse wird als aktive URL gespeichert.
    </small>

    @if(!empty($wledIdentity['mac']))
    <small class="help">MAC: {{ $wledIdentity['mac'] }}</small>
    @endif

    @foreach($aliases as $alias)
        @php
            $url = str_starts_with($alias, 'http://') || str_starts_with($alias, 'https://')
                ? $alias
                : 'http://' . $alias;
            $checked = $preferredUrl === $url || ($preferredUrl === null && ($item->url ?? null) === $url);
        @endphp
        <label class="wled-network-option">
            <input
                type="radio"
                name="wled_selected_url"
                value="{{ $url }}"
                {{ $checked ? 'checked' : '' }}
            />
            <span>{{ $alias }}</span>
        </label>
    @endforeach

    @if(!empty($wledIdentity['mac']))
    <input type="hidden" name="config[wled_identity][mac]" value="{{ $wledIdentity['mac'] }}" />
    @endif

    @if(!empty($wledIdentity['mdns']))
    <input type="hidden" name="config[wled_identity][mdns]" value="{{ $wledIdentity['mdns'] }}" />
    @endif

    @foreach($aliases as $alias)
    <input type="hidden" name="config[wled_identity][aliases][]" value="{{ $alias }}" />
    @endforeach

    @if($preferredUrl)
    <input type="hidden" name="config[wled_preferred_url]" value="{{ $preferredUrl }}" />
    @endif
</div>
