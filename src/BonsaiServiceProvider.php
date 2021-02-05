<?php
namespace Caffeinated\Bonsai;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BonsaiServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('bonsai', function($expression) {
            eval("\$params = [$expression];");
            switch($params[0]) {
                case "add":
                    return "<?php Bonsai::get()->add('$params[1]'".(isset($params[2])&&!empty($params[2])?",'$params[2]'":'').")".(isset($params[3])&&!empty($params[3])?"->dependsOn('$params[3]')":'')."; ?>";
                case "css":
                    return "<?php echo Bonsai::get()->css(); ?>";
                case "js":
                    return "<?php echo Bonsai::get()->js(); ?>";
                default:
                    throw new Exception('Invalid asset type declared. Must be either "css" or "js".');
            }
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('bonsai', function ($app) {
            return new Bonsai($app['view']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Bonsai::class];
    }
}
