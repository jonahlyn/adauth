<?php namespace Unm\Adauth;

use Illuminate\Support\ServiceProvider;

/**
 * Class AdauthServiceProvider
 * @package Unm\Adauth
 */
class AdauthServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('unm/adauth');

    //Add the AD Auth driver
    $this->app['auth']->extend('adauth', function($app)
    {
      return new AdauthGuard(
        new AdAuthUserProvider(
          $app['config']->get('adauth::adauth')
        ),
        $app->make('session.store')
      );
    });
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('adauth');
	}

}