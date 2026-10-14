<?php
declare(strict_types=1);

use src\Controllers\AuthController;
use src\Controllers\DeadlineController;
use src\Controllers\GoalController;
use src\Controllers\PhaseController;
use src\Controllers\TaskController;
use src\Core\Request;
use src\Core\Response;
use src\Core\Router;
use src\Middleware\AuthMiddleware;

#khai báo nhóm route /api/...
$router->group(
    ['prefix' => '/api',],
    function (Router $router): void {
        $router->get(
            '/health',
            static function (Request $request, array $params): Response {
                return Response::success([
                    'status' => 'ok',
                    'time' => date('c'),
                ]);
            }
        );

        #xác thực cho đăng ký và đăng nhập
        $router->post('/auth/register', [AuthController::class, 'register']);
        $router->post('/auth/login', [AuthController::class, 'login']);

        $router->group(
            [
                'middleware' => [
                    AuthMiddleware::class,
                ],
            ],
            function (Router $router): void {
                #lấy info account đang login
                $router->get(
                    '/auth/me',
                    [AuthController::class, 'me']
                );

                #route mục tiêu
                //danh sách
                $router->get('/goals', [GoalController::class, 'index']);
                //tạo
                $router->post('/goals', [GoalController::class, 'store']);
                //lấy 1 mục tiêu
                $router->get('/goals/{id}', [GoalController::class, 'show']);
                //cập nhật
                $router->put('/goals/{id}', [GoalController::class, 'update']);
                $router->patch('/goals/{id}', [GoalController::class, 'update']);
                //xóa
                $router->delete('/goals/{id}', [GoalController::class, 'destroy']);

                #route giai đoạn
                $router->get('/goals/{goalId}/phases', [PhaseController::class, 'index']);
                $router->post('/goals/{goalId}/phases', [PhaseController::class, 'store']);
                $router->put('/goals/{goalId}/phases/reorder', [PhaseController::class, 'reorder']);
                $router->get('/phases/{id}', [PhaseController::class, 'show']);
                $router->put('/phases/{id}', [PhaseController::class, 'update']);
                $router->patch('/phases/{id}', [PhaseController::class, 'update']);
                $router->delete('/phases/{id}', [PhaseController::class, 'destroy']);

                #route nhiệm vụ
                $router->get('/tasks', [TaskController::class, 'index']);
                $router->get('/goals/{goalId}/tasks', [TaskController::class, 'indexByGoal']);
                $router->post('/goals/{goalId}/tasks', [TaskController::class, 'store']);
                $router->get('/tasks/{id}', [TaskController::class, 'show']);
                $router->put('/tasks/{id}', [TaskController::class, 'update']);
                $router->patch('/tasks/{id}', [TaskController::class, 'update']);
                $router->patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
                $router->delete('/tasks/{id}', [TaskController::class, 'destroy']);

                #route deadline
                $router->get('/deadlines/summary', [DeadlineController::class, 'summary']);
                $router->get('/deadlines/overdue', [DeadlineController::class, 'overdue']);
                $router->get('/deadlines/upcoming', [DeadlineController::class, 'upcoming']);
                $router->patch('/goals/{id}/deadline', [DeadlineController::class, 'updateGoal']);
                $router->patch('/phases/{id}/deadline', [DeadlineController::class, 'updatePhase']);
                $router->patch('/tasks/{id}/deadline', [DeadlineController::class, 'updateTask']);
            }
        );
    }
);