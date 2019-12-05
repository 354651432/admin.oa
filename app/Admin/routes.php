<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->get('/', 'HomeController@index');

    $router->resource('/departments', DepartmentController::class);

    $router->resource('/flow/operator', Flow\OperatorController::class);

    $router->resource('/flow', Flow\FlowController::class);
    $router->get('/flow/control/{id}', "Flow\FlowController@control");
    $router->post('/flow/render', "Flow\FlowController@formRender");
    $router->post('/flow/saveControl', "Flow\FlowController@saveControl");
    $router->post('/flow/preview', 'Flow\FlowController@preview');
    $router->get('/flow/preview', 'Flow\FlowController@preview'); // todo
    $router->post('/flow/preapply', 'Flow\FlowController@preApply'); // todo
    $router->get('/flow/preapply', 'Flow\FlowController@preApply'); // todo

    $router->get('/flow/{id}/edit-node', 'Flow\FlowController@editNode');
    $router->post('/flow/{id}/edit-node', 'Flow\FlowController@storeNode');
    $router->get('/flow/{id}/edit-form', 'Flow\FlowController@editForm');
    $router->post('/flow/{id}/edit-form', 'Flow\FlowController@storeForm');

    $router->get('/userflows', 'Flow\UserFlowController@index');
    $router->get('/userflows/{id}/new', 'Flow\UserFlowController@userflow');
    $router->post('/userflows/{id}/new', 'Flow\UserFlowController@userflowPost');

    $router->get('/userflows/record', 'Flow\UserFlowController@record');
    $router->get('/userflows/{id}', 'Flow\UserFlowController@show')
        ->where("id", "[0-9.]+")->name("userflow");
    $router->post('/userflows/approval', 'Flow\UserFlowController@approvalPost');

    $router->get('/userflows/approval', 'Flow\UserFlowController@approval');

    $router->get('/userflows/notify', 'Flow\UserFlowController@notify');
    $router->get('/userflows/recv', 'Flow\UserFlowController@recv');
    $router->get('/userflows/{id}/cancel', 'Flow\UserFlowController@cancel');
    $router->post('/userflows/pass', 'Flow\UserFlowController@pass');
    $router->post('/userflows/passConfirm', 'Flow\UserFlowController@passConfirm');
    $router->post('/userflows/notice', 'Flow\UserFlowController@notice');

    $router->post('/userflows/comment', 'Flow\UserFlowController@addComment');
    $router->post('/userflows/{id}/attachment', 'Flow\UserFlowController@attachment');
});
