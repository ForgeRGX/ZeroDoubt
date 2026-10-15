<?php
declare(strict_types=1);

return [
    ['GET',  '/login',                       [AuthController::class, 'showLogin']],
    ['POST', '/login',                       [AuthController::class, 'login']],
    ['GET',  '/register',                    [AuthController::class, 'showRegister']],
    ['POST', '/register',                    [AuthController::class, 'register']],
    ['POST', '/logout',                      [AuthController::class, 'logout']],

    ['GET',  '/',                            [DashboardController::class, 'index']],
    ['GET',  '/reports',                     [ReportController::class, 'index']],

    ['GET',  '/settings',                    [SettingsController::class, 'index']],
    ['POST', '/settings/language',           [SettingsController::class, 'language']],
    ['POST', '/settings/password',           [SettingsController::class, 'password']],

    ['GET',  '/transactions',                [TransactionController::class, 'index']],
    ['GET',  '/transactions/export',         [TransactionController::class, 'export']],
    ['GET',  '/transactions/new',            [TransactionController::class, 'create']],
    ['POST', '/transactions',                [TransactionController::class, 'store']],
    ['GET',  '/transactions/{id}/edit',      [TransactionController::class, 'edit']],
    ['POST', '/transactions/{id}',           [TransactionController::class, 'update']],
    ['POST', '/transactions/{id}/delete',    [TransactionController::class, 'delete']],

    ['GET',  '/pending',                     [PendingController::class, 'index']],
    ['POST', '/pending/confirm-all',         [PendingController::class, 'confirmAll']],
    ['POST', '/pending/{id}/confirm',        [PendingController::class, 'confirm']],
    ['POST', '/pending/{id}/skip',           [PendingController::class, 'skip']],

    ['GET',  '/recurring',                   [RecurringController::class, 'index']],
    ['GET',  '/recurring/new',               [RecurringController::class, 'create']],
    ['POST', '/recurring',                   [RecurringController::class, 'store']],
    ['GET',  '/recurring/{id}/edit',         [RecurringController::class, 'edit']],
    ['POST', '/recurring/{id}',              [RecurringController::class, 'update']],
    ['POST', '/recurring/{id}/toggle',       [RecurringController::class, 'toggle']],
    ['POST', '/recurring/{id}/delete',       [RecurringController::class, 'delete']],

    ['GET',  '/categories',                  [CategoryController::class, 'index']],
    ['GET',  '/categories/new',              [CategoryController::class, 'create']],
    ['POST', '/categories',                  [CategoryController::class, 'store']],
    ['GET',  '/categories/{id}/edit',        [CategoryController::class, 'edit']],
    ['POST', '/categories/{id}',             [CategoryController::class, 'update']],
    ['POST', '/categories/{id}/delete',      [CategoryController::class, 'delete']],

    ['GET',  '/budgets',                     [BudgetController::class, 'index']],
    ['POST', '/budgets',                     [BudgetController::class, 'save']],
];
