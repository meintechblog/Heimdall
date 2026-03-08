<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request as Input;
use Yaml;

abstract class Search
{
    /**
     * List of all search providers
     *
     * @return Collection
     */
    public static function providers(): Collection
    {
        $providers = self::standardProviders();
        $providers = $providers + self::appProviders();

        return collect($providers);
    }

    /**
     * Gets details for a single provider
     *
     * @return false|object
     */
    public static function providerDetails($provider)
    {
        $providers = self::providers();
        if (! isset($providers[$provider])) {
            return false;
        }

        return (object) $providers[$provider] ?? false;
    }

    /**
     * Array of the standard providers
     *
     * @return array
     */
    public static function standardProviders(): array
    {
        // $providers = json_decode(file_get_contents(storage_path('app/searchproviders.json')));
        // print_r($providers);
        $providers = Yaml::parseFile(storage_path('app/searchproviders.yaml'));
        $all = [];
        foreach ($providers as $key => $provider) {
            $all[$key] = $provider;
            $all[$key]['type'] = 'standard';
        }

        return $all;
    }

    /**
     * Loops through users apps to see if app is a search provider, might be worth
     * looking into caching this at some point
     *
     * @return array
     */
    public static function appProviders(): array
    {
        $providers = [];
        $userapps = Item::all();
        foreach ($userapps as $app) {
            if (empty($app->class)) {
                continue;
            }
            if (($provider = Item::isSearchProvider($app->class)) !== false) {
                $name = Item::nameFromClass($app->class);
                $providers[$app->id] = [
                    'id' => $app->id,
                    'type' => $provider->type,
                    'class' => $app->class,
                    'url' => $app->url,
                    'name' => $app->title,
                    'colour' => $app->colour,
                    'icon' => $app->icon,
                    'description' => $app->description,
                ];
            }
        }

        return $providers;
    }

    /**
     * Outputs the search form
     *
     * @return string
     */
    public static function form(): string
    {
        $output = '';
        $homepage_search = Setting::fetch('homepage_search');

        if ((bool) $homepage_search !== true) {
            return $output;
        }

        $output .= '<div class="searchform">';
        $output .= '<form action="https://www.google.com/search" target="_blank" method="get">';
        $output .= '<div id="search-container" class="input-container">';
        $output .= '<input type="text" name="q" value="'.e(Input::get('q') ?? '').'" class="homesearch" autofocus placeholder="'.__('app.settings.search').'..." />';
        $output .= '<button type="submit">'.ucwords(__('app.settings.search')).'</button>';
        $output .= '</div>';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }
}
