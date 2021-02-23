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
        Blade::directive('bonsai', function($assetType) {
            switch($assetType) {
                case "'css'":
                    return "<?php echo Bonsai::get()->css(); ?>";
                case "'js'":
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
