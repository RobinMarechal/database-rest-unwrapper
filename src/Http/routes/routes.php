<?php
/**
 * Created by PhpStorm.
 * User: Utilisateur
 * Date: 25/09/2017
 * Time: 02:26
 */
$controllerNamespace = '\RobinMarechal\DatabaseRestUnwrapper\Http\Controllers';

Route::any("api/{resource}/{id?}/{relation?}/{relatedId?}", "$controllerNamespace\RestController@dispatch")->name('api.dispatch');