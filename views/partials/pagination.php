<?php
/** @var int $page @var int $pages @var array $query @var string $basePath */
if ($pages <= 1) {
    return;
}
$link = static fn(int $p): string => e(url($basePath, $query + ['page' => $p]));
$from = max(1, $page - 2);
$to = min($pages, $page + 2);
?>
<nav aria-label="<?= e(t('Paginación')) ?>">
    <ul class="pagination justify-content-center flex-wrap mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $link(max(1, $page - 1)) ?>">&laquo;</a>
        </li>
        <?php if ($from > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $link(1) ?>">1</a></li>
            <?php if ($from > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $from; $p <= $to; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link($p) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($to < $pages): ?>
            <?php if ($to < $pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= $link($pages) ?>"><?= $pages ?></a></li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $link(min($pages, $page + 1)) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
