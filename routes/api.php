<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post( 'report/project', 'ReportController@general' )->middleware('jwt.auth');

//AUTHENTIFICATION
Route::post('authenticate', 'AuthenticateController@authenticate')->middleware('throttle:10,1,login');
Route::post('logout', 'AuthenticateController@logout')->middleware('jwt.auth');
Route::post('forgot/password', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::post('reset/password', 'Auth\ResetPasswordController@reset');
Route::post('companies/list', 'ConfigurationController@listCompany');

//PUBLIC
Route::post( 'report/public', 'ReportController@public' );
Route::post( 'report/donor', 'ReportController@donor' );
Route::post( 'report/ally', 'ReportController@ally' );


Route::get('operation/balance/account', 'OperationController@balanceAccount')->middleware('jwt.auth');
Route::post('operation/balance/accounts/details', 'OperationController@balanceDetails')->middleware('jwt.auth');
Route::get('operation/balance/account', 'OperationController@balanceAccount')->middleware('jwt.auth');


Route::post('donor/operations/info', 'ContactController@resumeOperations')->middleware('jwt.auth');
Route::post('project/collect/monitoring', 'ProjectController@collectMonitoring')->middleware('jwt.auth');


Route::group(['middleware' => ['jwt.auth' ]], function()
{
    
    Route::post('donors/balance/report', 'ReportController@donorsBalance');
    //ADMIN
    Route::post('get/session', 'UserController@session');
    Route::get('user/data', 'UserController@data')->middleware('users.manage');
    Route::get('user/list', 'UserController@index');
    Route::post('user/store', 'UserController@store')->middleware('request.sanitize')->middleware('users.manage');
    Route::post('user/show', 'UserController@show')->middleware('users.manage');
    Route::post('user/update', 'UserController@update')->middleware(['auth.role:admin,user', 'request.sanitize'])->middleware('users.manage');
    Route::post('user/delete', 'UserController@delete')->middleware('auth.role:admin,user')->middleware('users.manage');
    
    //ACTIONS
    Route::get('action/index', 'ActionController@index');
    Route::post('action/select', 'ActionController@select');
    Route::post('action/show', 'ActionController@show');
    
    //ACCOUNTS
    Route::post('account/index', 'AccountController@index');
    Route::post('account/store', 'AccountController@store')->middleware('request.sanitize');
    Route::post('account/delete', 'AccountController@delete')->middleware('auth.role:admin');
    Route::post('account/show', 'AccountController@show');
    Route::post('account/update', 'AccountController@update')->middleware(['auth.role:admin,user', 'request.sanitize']);
    
    //OPERATIONS
    Route::post('operation/index', 'OperationController@index');
    Route::post('operation/data', 'OperationController@data');
    Route::post('operation/store', 'OperationController@store')->middleware('request.sanitize');
    Route::post('operation/delete', 'OperationController@delete');
    Route::post('operation/replicate', 'OperationController@replicate')->middleware('auth.role:admin');
    Route::post('operation/show', 'OperationController@show');
    Route::post('operation/update', 'OperationController@update')->middleware(['auth.role:admin,user', 'request.sanitize']);
    Route::post('operation/select', 'OperationController@select');
    
    Route::post('donor/store/operations/balance', 'OperationController@storeBalance');
    
    // Route::post('operation/donors/select', 'OperationController@reportOperationsDonorsNew');
    // Route::post('operation/donors/balance', 'OperationController@reportOperationsDonorsNew');
    Route::post('operation/donors/monitoring/select', 'OperationController@operationsDonorsMonitoring');
    Route::post('operation/upload', 'OperationController@upload');  
    
    Route::post('operation/import/masive/projects', 'LayoutController@uploadMasiveProjects');  
    Route::post('operation/import/masive/incomes', 'LayoutController@uploadMasiveIncomes');  
    Route::post('operation/import/masive/outcomes', 'LayoutController@uploadMasiveOutcomes');  
    Route::post('operation/import/masive/transfers', 'LayoutController@uploadMasiveTransfers');
    Route::post('/operation/history/select', 'OperationController@operationHistory');
    Route::post('/operation/history/delete', 'OperationController@deleteOperationHistory');
    
    
    // Route::post('operation/upload', 'LayoutController@upload');    
    //    Route::get('operation/balance/account', 'OperationController@balanceAccount')->middleware('jwt.auth');
    
    // PARAMETERS
    Route::post('parameters/listing', 'ParametersController@listing');
    Route::post('parameters/delete', 'ParametersController@delete');
    Route::post('parameters/store', 'ParametersController@store');
    Route::post('parameters/update', 'ParametersController@update');
    Route::post('parameters/show', 'ConfigurationController@parameters');
    Route::post('configuration/company', 'ConfigurationController@company');
    Route::post('configuration/update/company', 'ConfigurationController@updateConfigurationCompany');
    Route::put('parameters/update/categories', 'ConfigurationController@updateParametersCategories');
    
    // PROGRAMS
    Route::get('program/listing', 'ProgramController@listing'); 
    Route::post('program/store', 'ProgramController@store'); 
    Route::post('program/update', 'ProgramController@update'); 

    // PROJECTS
    Route::get('project/listing', 'ProjectController@listing'); 
    Route::get('project/data', 'ProjectController@dataForm'); 
    Route::get('project/show/{id}', 'ProjectController@show'); 
    Route::post('project/select', 'ProjectController@select'); 
    Route::post('project/data', 'ProjectController@data'); 
    Route::post('project/store', 'ProjectController@store')->middleware('request.sanitize'); 
    Route::post('project/delete', 'ProjectController@delete'); 
    Route::post('project/duplicate', 'ProjectController@duplicate')->middleware('auth.role:admin'); 
    Route::post('project/show', 'ProjectController@show'); 
    Route::post('project/update', 'ProjectController@update')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('project/showProgram', 'ProjectController@showProgram'); 
    Route::post('project/upload/attachments', 'ProjectController@uploadAttachments'); 
    Route::post('project/remove/attachment', 'ProjectController@removeAttachments');
    Route::post('project/validateConta', 'ProjectController@validateConta');
    
    // TASKS
    Route::post('task/select', 'TaskController@select'); 
    Route::post('task/select/pivot', 'TaskController@pivot'); 
    Route::post('task/select/reminder', 'TaskController@selectReminder'); 
    Route::post('task/select/operations', 'TaskController@selectOperations'); 
    Route::post('task/data', 'TaskController@data'); 
    
    //TASKS STORE
    Route::post('task/store', 'TaskController@store'); 
    Route::post('task/store', 'TaskController@store')->middleware('request.sanitize'); 
    Route::post('task/duplicate', 'TaskController@duplicate'); 
    Route::post('task/delete', 'TaskController@delete')->middleware('auth.role:admin'); 
    Route::post('task/delete/step', 'TaskController@deleteStep')->middleware('auth.role:admin'); 
    Route::post('task/show', 'TaskController@show'); 
    Route::post('task/update', 'TaskController@update')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('task/update/general', 'TaskController@updateGeneral')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('task/update/contacts', 'TaskController@updateContacts')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('task/update/documents', 'TaskController@updateDocuments')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('task/update/operations', 'TaskController@updateOperations')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('/task/update/volunteers', 'TaskController@updateVolunteers')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('/task/update/indicators', 'TaskController@updateIndicators')->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('/task/data/operations', 'TaskController@dataOperations'); 
    Route::post('task/transfer/donations', 'TaskController@transferOperations'); 

    /************************
     *     COMMON MENU
     ************************/

    //EVENTS
    Route::get('event/index', 'EventController@index');
    Route::post('event/index', 'EventController@index');
    Route::post('event/store', 'EventController@store')->middleware('request.sanitize');
    Route::post('event/show', 'EventController@show');
    Route::put('event/update', 'EventController@update')->middleware(['auth.role:admin,user', 'request.sanitize']);
    Route::post('event/delete', 'EventController@delete')->middleware('auth.role:admin');
    Route::delete('event/destroy', 'EventController@destroy')->middleware('auth.role:admin');

    //CONTACTS
    Route::get('contact/index', 'ContactController@index');
    Route::get('contact/data', 'ContactController@data');
    Route::post('contact/select', 'ContactController@select');
    Route::post('contact/select2', 'ContactController@select2');
    Route::post('contact/store', 'ContactController@store')->middleware('request.sanitize');
    Route::post('contact/show', 'ContactController@show');
    Route::post('contact/update', 'ContactController@update')->middleware(['auth.role:admin,user', 'request.sanitize']);
    Route::post('contact/delete', 'ContactController@delete')->middleware('auth.role:admin');
    Route::delete('contact/destroy', 'ContactController@destroy')->middleware('auth.role:admin');
    Route::post('contact/send/notification', 'ContactController@sendNotification');
    Route::post('contact/store/transaction', 'ContactController@storeTransaction');
    Route::post('contact/listCategory', 'ContactController@listCategory')->middleware();
    
    // Route::post('donor/operations/info', 'ContactController@resumeOperations')->middleware('jwt.auth');



    //LIBRARY
    Route::post('library/index' , 'ContentController@index' ); 
    Route::post('library/data' , 'ContentController@data' ); 
    Route::post('library/select' , 'ContentController@select' ); 
    Route::post('library/store' , 'ContentController@store' )->middleware('request.sanitize'); 
    Route::post('library/show' , 'ContentController@show' ); 
    Route::put('library/update' , 'ContentController@update' )->middleware(['auth.role:admin,user', 'request.sanitize']); 
    Route::post('library/delete' , 'ContentController@delete' )->middleware('auth.role:admin'); 

    //REPORTS
    Route::post( 'report/data', 'ReportController@data' );
   // Route::post( 'report/project', 'ReportController@general' )->middleware('jwt.auth');
    Route::post( 'report/operations', 'ReportController@operations' );
    Route::post( 'report/dashboard', 'ReportController@dashboard' );
    Route::post( 'report/donors', 'ReportController@donors' );
    Route::post( 'report/donors/data', 'ReportController@donorsData' );
    
    // SUPPORT
    Route::post( 'support/request', 'SupportController@requestSupport' );
    Route::post( 'delete/files', 'UtilsController@deleteFiles' );
    
    // GOOD AND SERVICES
    Route::post( 'goods/and/services/select', 'InventoryController@select' );
    Route::post( 'goods/and/services/list', 'InventoryController@index' );
    Route::post( 'goods/and/services/data', 'InventoryController@data' );
    Route::post( 'goods/and/services/show', 'InventoryController@show' );
    Route::post( 'goods/and/services/update', 'InventoryController@update' );
    Route::post( 'goods/and/services/store', 'InventoryController@store' );
    Route::post( 'goods/and/services/delete', 'InventoryController@delete' );

    //ORDER PAY
    Route::post('order/pay/index', 'OrderPayController@index');
    Route::post('order/pay/select', 'OrderPayController@select');
    Route::post('order/pay/delete', 'OrderPayController@delete');
    Route::post('order/pay/authorization', 'OrderPayController@authorization');
    Route::post('order/pay/data', 'OrderPayController@data');
    Route::post('order/pay/store', 'OrderPayController@store')->middleware('request.sanitize');;
    Route::post('order/pay/update', 'OrderPayController@update')->middleware('request.sanitize');
    Route::post('order/pay/show', 'OrderPayController@show');
    //ORDER COLECT
    Route::post('order/collect/index', 'OrderCollectController@index');
    Route::post('order/collect/select', 'OrderCollectController@select');
    Route::post('order/collect/delete', 'OrderCollectController@delete');
    Route::post('order/collect/authorization', 'OrderCollectController@authorization');
    Route::post('order/collect/data', 'OrderCollectController@data');
    Route::post('order/collect/store', 'OrderCollectController@store')->middleware('request.sanitize');;
    Route::post('order/collect/update', 'OrderCollectController@update')->middleware('request.sanitize');
    Route::post('order/collect/show', 'OrderCollectController@show');

    
    // NOTIFICATIONS
    Route::post('notifications/data', 'NotificationController@data');
    Route::post('notifications/send', 'NotificationController@send');
    Route::post('notifications/show', 'NotificationController@show');
    Route::post('notifications/responseAuth', 'NotificationController@responseAuth');
    Route::post('notification/request/auth', 'NotificationController@requestAuthDocument');
    Route::post('notifications/unread', 'NotificationController@unread');
    Route::post('notifications/select', 'NotificationController@select');
    Route::post('notifications/delete', 'NotificationController@delete');
    Route::post('notifications/list', 'NotificationController@selectRecurrent');

    Route::post('content/update/priority', 'ContentController@updatePriority');


    Route::post('content/show', 'ContentController@show'); 

    Route::post('content/update/proofs', 'ContentController@updateProofs');
    
});


