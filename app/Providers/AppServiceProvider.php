<?php

namespace App\Providers;

use App\Application;
use App\Jobs\ProcessApps;
use App\Jobs\UpdateApps;
use App\Setting;
use App\User;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use App\Services\CustomFormBuilder;
use Spatie\Html\Html;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! class_exists('ZipArchive')) {
            die('You are missing php-zip');
        }

        $this->createEnvFile();

        $this->setupDatabase();

        if (! is_file(public_path('storage/.gitignore'))) {
            Artisan::call('storage:link');
            \Session::put('current_user', null);
        }

        $this->registerGlobalViewState($this->app->runningUnitTests() || $this->app->runningConsoleCommand('test'));

        if ($this->app->runningUnitTests() || $this->app->runningConsoleCommand('test')) {
            $this->app['view']->addNamespace('SupportedApps', app_path('SupportedApps'));

            if (env('FORCE_HTTPS') === true) {
                \URL::forceScheme('https');
            }

            if (env('APP_URL') != 'http://localhost') {
                \URL::forceRootUrl(env('APP_URL'));
            }

            return;
        }

        $applications = Application::all();

        if ($applications->count() <= 0) {
            ProcessApps::dispatch();
        }

        $lang = Setting::fetch('language') ?: config('app.locale', 'en');
        \App::setLocale($lang);

        $this->app['view']->addNamespace('SupportedApps', app_path('SupportedApps'));

        if (env('FORCE_HTTPS') === true) {
            \URL::forceScheme('https');
        }

        if (env('APP_URL') != 'http://localhost') {
            \URL::forceRootUrl(env('APP_URL'));
        }
    }

    /**
     * Generate app key if missing and .env exists
     */
    public function genKey(): void
    {
        if (is_file(base_path('.env'))) {
            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true, '--no-interaction' => true]);
            }
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->singleton('custom-form', function ($app) {
            return new CustomFormBuilder($app->make(Html::class));
        });

        $this->app->singleton('settings', function () {
            return new Setting();
        });
    }

    /**
     * Check if database needs an update or do first time database setup
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setupDatabase(): void
    {
        if (! $this->shouldRunAutomaticDatabaseSetup()) {
            return;
        }

        $db_type = config()->get('database.default');

        if ($db_type == 'sqlite') {
            $db_file = database_path(env('DB_DATABASE', 'app.sqlite'));
            Log::debug('SQLite Database Path: ' . $db_file);
            if (! is_file($db_file)) {
                touch($db_file);
            }
        }

        if ($this->needsDBUpdate()) {
            Artisan::call('migrate', ['--path' => 'database/migrations', '--force' => true, '--seed' => true]);
            ProcessApps::dispatchSync();
            $this->updateApps();
        }
    }

    public function createEnvFile(): void
    {
        if (!is_file(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        $this->genKey();
    }

    private function needsDBUpdate(): bool
    {
        if (!Schema::hasTable('settings')) {
            return true;
        }

        $db_version = Setting::_fetch('version');
        $app_version = config('app.version');

        return version_compare($app_version, $db_version) === 1;
    }

    protected function shouldRunAutomaticDatabaseSetup(): bool
    {
        return ! (
            $this->app->runningUnitTests()
            || $this->app->runningConsoleCommand('test')
        );
    }

    protected function registerGlobalViewState(bool $isTestEnvironment): void
    {
        view()->composer('*', function ($view) use ($isTestEnvironment) {
            if ($isTestEnvironment) {
                $view->with('alt_bg', '');
                $view->with('trianglify', 'false');
                $view->with('trianglify_seed', null);
                $view->with('allusers', collect());
                $view->with('current_user', new User([
                    'id' => 0,
                    'username' => 'test',
                    'avatar' => null,
                ]));
                $view->with('enable_auth_admin_controls', true);

                return;
            }

            if (isset($_SERVER['HTTP_AUTHORIZATION']) && ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
                explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }
            if (! \Auth::check()) {
                if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])
                        && ! empty($_SERVER['PHP_AUTH_USER']) && ! empty($_SERVER['PHP_AUTH_PW'])) {
                    $credentials = ['username' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']];

                    if (\Auth::attempt($credentials, true)) {
                        $user = \Auth::user();
                        session(['current_user' => $user]);
                    }
                } elseif (isset($_SERVER['REMOTE_USER']) && ! empty($_SERVER['REMOTE_USER'])) {
                    $user = User::where('username', $_SERVER['REMOTE_USER'])->first();
                    if ($user) {
                        \Auth::login($user, true);
                        session(['current_user' => $user]);
                    }
                }
            }

            $alt_bg = '';
            $trianglify = 'false';
            $trianglify_seed = null;
            if (Setting::fetch('trianglify')) {
                $trianglify = 'true';
                $trianglify_seed = Setting::fetch('trianglify_seed');
            } elseif ($bg_image = Setting::fetch('background_image')) {
                $alt_bg = ' style="background-image: url(storage/'.$bg_image.')"';
            }

            $view->with('alt_bg', $alt_bg);
            $view->with('trianglify', $trianglify);
            $view->with('trianglify_seed', $trianglify_seed);
            $view->with('allusers', User::all());
            $view->with('current_user', User::currentUser());
            if (config('app.auth_roles_enable')) {
                $view->with('enable_auth_admin_controls', in_array(config('app.auth_roles_admin'), explode(config('app.auth_roles_delimiter'), $_SERVER[config('app.auth_roles_http_header')])));
            } else {
                $view->with('enable_auth_admin_controls', true);
            }
        });
    }

    private function updateApps(): void
    {
        // This lock ensures that the job is not invoked multiple times.
        // In 5 minutes all app updates should be finished.
        $lock = Cache::lock('updateApps', 5*60);

        if ($lock->get()) {
            UpdateApps::dispatchAfterResponse();
        }
    }
}
