<?php

namespace RobinMarechal\DatabaseRestUnwrapper;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class DatabaseRestUnwrapperServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 * @return void
	 */
	public function boot ()
	{
		$this->setupRoutes($this->app->router);
	}


	/**
	 * Define the routes for the application.
	 *
	 * @param \Illuminate\Routing\Router|Router $router
	 *
	 * @return void
	 */
	public function setupRoutes (Router $router)
	{
		$router->group(['namespace' => 'RobinMarechal\DatabaseRestUnwrapper\Http\Controllers'], function ($router) {
			require __DIR__ . '/Http/routes/routes.php';
		});
	}


	/**
	 * Register any package services.
	 * @return void
	 */
	public function register ()
	{
		//
	}
}