<?php
    /** @var string $content */
    $authed = Auth::check();
    $pendingCount = $authed ? Transaction::pendingCount() : 0;
    $nav = [
        ['/', 'Dashboard', 'bi-speedometer2'],
        ['/transactions', 'Movimientos', 'bi-arrow-left-right'],
        ['/recurring', 'Recurrentes', 'bi-arrow-repeat'],
        ['/budgets', 'Presupuestos', 'bi-bullseye'],
        ['/reports', 'Informes', 'bi-bar-chart-line'],
        ['/categories', 'Categorías', 'bi-tags'],
    ];
    $publicPaths = ['/login', '/register'];
    $path        = current_path();
?>
<!doctype html>
<html lang="<?= e(I18n::lang()) ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#e3f1ec">
        <link rel="icon" type="image/svg+xml" href="/static/img/favicon.svg">
        <title><?= e($title ?? 'Zero Doubt') ?> • Zero Doubt</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Instrument+Sans:wght@400..700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="/static/css/app.css" rel="stylesheet">
    </head>
    <body>
        <?php if (!in_array($path, $publicPaths, true)): ?>
            <header class="app-header sticky-top">
                <div class="container">
                    <nav class="navbar navbar-expand-xl app-nav">
                        <a class="navbar-brand" href="/"><img src="/static/img/logo_full.png" alt="Zero Doubt logo" height="30" class="brand-mark"></a>
                        <?php if ($authed): ?>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                                aria-controls="mainNav" aria-expanded="false" aria-label="<?= e(t('Abrir menú')) ?>">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="mainNav">
                            <ul class="navbar-nav ms-auto">
                                <?php foreach ($nav as [$navPath, $label, $icon]): ?>
                                    <li class="nav-item">
                                        <a class="nav-link<?= nav_active($navPath) ? ' active' : '' ?>" href="<?= e($navPath) ?>">
                                            <i class="bi <?= e($icon) ?>"></i><?= e(t($label)) ?>
                                            <?php if ($navPath === '/recurring' && $pendingCount > 0): ?>
                                                <span class="nav-badge" title="<?= e(t('Pendientes de validar')) ?>"><?= (int) $pendingCount ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="nav-user">
                                <a class="nav-user-name<?= nav_active('/settings') ? ' active' : '' ?>" href="/settings" title="<?= e(t('Configuración')) ?>"><i class="bi bi-person-circle"></i><?= e(Auth::name()) ?><i class="bi bi-gear ms-2 text-muted"></i></a>
                                <form method="post" action="/logout" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i><?= t('Salir') ?></button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </nav>
                </div>
            </header>
        <?php endif; ?>

        <main class="container py-4">
            <?php foreach (flashes() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= e(t('Cerrar')) ?>"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>

        <footer class="container pb-4 text-center text-muted small">
            <?= t('Registro personal de ingresos y gastos. Tus datos son privados: solo los ves tú.') ?>
            <br>
            <span class="d-inline-block mt-2">Zero Doubt <?= e(env('APP_VERSION', 'dev')) ?> (<?= e(env('APP_COMMIT', 'local')) ?>)</span>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <?php if (!empty($withCharts)): ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
            <script src="/static/js/charts.js"></script>
        <?php endif; ?>
        <script src="/static/js/app.js"></script>
    </body>
</html>
