<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('bot/set-webhook', 'BotController@setWebhook');
$router->get('bot/remove-webhook', 'BotController@removeWebhook');
$router->post('bot/webhook', [
    'as' => 'webhook',
    'uses' => 'BotController@webhook',
]);
$router->get('bot', 'BotController@index');
