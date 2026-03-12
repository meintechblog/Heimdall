                    @php
                        $isFileFlowsTile = $app->class === 'App\\SupportedApps\\FileFlows\\FileFlows' && $app->enabled();
                        $liveAppName = $app->class ? App\Item::nameFromClass($app->class) : '';
                    @endphp
                    <section class="item-container{{ $app->droppable . ' ' . $app->getTagClass()}}" data-name="{{ $app->title }}" data-id="{{ $app->id }}">
                        <div class="item" style="background-color: {{ $app->colour }}">
                            <div class="app-icon-container">
                                @if($app->icon)
                                <img class="app-icon" src="{{ asset('/storage/'.str_replace('supportedapps', 'icons', $app->icon)) }}" />
                                @else
                                <img class="app-icon" src="{{ asset('/img/heimdall-icon-small.png') }}" />
                                @endif
                                @if($liveAppName === 'VenusOS' && $app->enabled())
                                <div class="venus-icon-loading" aria-hidden="true">
                                    <span class="fileflows-toggle-visual">
                                        <span class="fileflows-toggle-loading fileflows-toggle-loading-ring" aria-hidden="true"></span>
                                    </span>
                                </div>
                                @endif
                                @if($isFileFlowsTile)
                                <button
                                    type="button"
                                    class="fileflows-processing-toggle"
                                    data-id="{{ $app->id }}"
                                    data-toggle-url="{{ route('items.fileflows.toggle', [$app->id]) }}"
                                    data-processing-state="loading"
                                    data-toggle-action=""
                                    aria-label="Loading FileFlows state"
                                    title="Loading FileFlows state"
                                >
                                    <span class="fileflows-toggle-loading" aria-hidden="true"></span>
                                </button>
                                @endif
                            </div>
                            <div class="details">
                                <div class="title{{ title_color($app->colour) }}">{{ $app->title }}</div>
                                @if($app->enabled())
                                <div data-id="{{ $app->id }}" data-live-app="{{ $liveAppName }}" data-dataonly="{{ $app->getconfig()->dataonly ?? '0' }}" class="livestats-container{{ title_color($app->colour) }}"></div>
                                @endif
                            </div>
                            <a rel="noopener noreferrer" onauxclick="this.blur();" onclick="this.blur();" class="link{{ title_color($app->colour) }}"{!! $app->link_target !!} href="{{ $app->link }}"><i class="fas {{ $app->link_icon }}"></i></a>
                            <?php /*
                            @if($app->enhanced() === true && (bool)$app->getConfig()->enabled === true)
                            <div class="tile-actions refresh">
                                <div class="icon">
                                    <i class="fas fa-arrow-rotate-right"></i>
                                </div>
                                Refresh Stats
                            </div>
                            @endif
                            */ ?>
                        </div>
                        <a class="item-edit" href="{{ route($app->link_type.'.edit', [ $app->id ]) }}"><i class="fas fa-pencil"></i></a>
                        @if((string)$app->appdescription !== '')<div class="tooltip">{{ $app->appdescription }}</div>@endif
                    </section>
