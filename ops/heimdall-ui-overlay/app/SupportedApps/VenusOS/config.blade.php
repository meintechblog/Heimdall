<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items" style="flex-wrap: wrap;">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>MQTT Port</label>
        {!! Form::text('config[mqtt_port]', isset($item) && isset($item->getconfig()->mqtt_port) ? $item->getconfig()->mqtt_port : 1883, ['placeholder' => '1883', 'data-config' => 'mqtt_port', 'class' => 'form-control config-item', 'inputmode' => 'numeric']) !!}
        <p style="font-size: .8em;">Requires <code>MQTT on LAN (Plaintext)</code> enabled on the Venus OS device.</p>
    </div>
    <div class="input">
        <label>Portal ID (optional)</label>
        {!! Form::text('config[portal_id]', isset($item) ? $item->getconfig()->portal_id : null, ['placeholder' => 'Auto-discover when blank', 'data-config' => 'portal_id', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
