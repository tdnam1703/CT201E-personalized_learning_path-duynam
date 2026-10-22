<?php
declare(strict_types=1);

$router->get('/', 'HomeController@index');

#xác thực
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

#mục tiêu học tập
$router->get('/goals', 'GoalController@index');
$router->get('/goals/create', 'GoalController@create');
$router->post('/goals', 'GoalController@store');
$router->get('/goals/{id}/edit', 'GoalController@edit');
$router->post('/goals/{id}/update', 'GoalController@update');
$router->post('/goals/{id}/delete', 'GoalController@destroy');

#chi tiết mục tiêu và giai đoạn
$router->get('/goals/{id}', 'GoalController@show');
$router->get('/goals/{goalId}/phases/create', 'PhaseController@create');
$router->post('/goals/{goalId}/phases', 'PhaseController@store');
$router->get('/phases/{id}/edit', 'PhaseController@edit');
$router->post('/phases/{id}/update', 'PhaseController@update');
$router->post('/phases/{id}/delete', 'PhaseController@destroy');

#nhiệm vụ
$router->get('/goals/{goalId}/tasks/create', 'TaskController@create');
$router->post('/goals/{goalId}/tasks', 'TaskController@store');
$router->get('/tasks/{id}/edit', 'TaskController@edit');
$router->post('/tasks/{id}/update', 'TaskController@update');
$router->post('/tasks/{id}/delete', 'TaskController@destroy');

#cập nhật nhanh tiến độ nhiệm vụ
$router->post('/tasks/{id}/status', 'TaskController@changeStatus');
$router->post('/tasks/{id}/progress', 'TaskController@changeProgress');

#bấm giờ học
$router->get('/study', 'StudyController@index');
$router->post('/study/start', 'StudyController@start');
$router->post('/study/{id}/stop', 'StudyController@stop');
$router->post('/study/{id}/delete', 'StudyController@destroy');

#thống kê tổng quan
$router->get('/dashboard', 'DashboardController@index');

#nhắc nhở & thông báo
$router->get('/reminders', 'ReminderController@index');
$router->post('/reminders', 'ReminderController@store');
$router->post('/reminders/{id}/toggle', 'ReminderController@toggle');
$router->post('/reminders/{id}/delete', 'ReminderController@destroy');
$router->get('/notifications', 'NotificationController@index');
$router->post('/notifications/read-all', 'NotificationController@markAllRead');
$router->post('/notifications/{id}/read', 'NotificationController@markRead');

#lịch học tập
$router->get('/schedule', 'ScheduleController@index');
$router->get('/schedule/create', 'ScheduleController@create');
$router->post('/schedule', 'ScheduleController@store');
$router->get('/schedule/{id}/edit', 'ScheduleController@edit');
$router->post('/schedule/{id}/update', 'ScheduleController@update');
$router->post('/schedule/{id}/delete', 'ScheduleController@destroy');

#gợi ý điều chỉnh lộ trình
$router->get('/suggestions', 'SuggestionController@index');
$router->post('/suggestions/{id}/accept', 'SuggestionController@accept');
$router->post('/suggestions/{id}/dismiss', 'SuggestionController@dismiss');