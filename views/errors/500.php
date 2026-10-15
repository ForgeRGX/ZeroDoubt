<div class="text-center py-5">
    <h1 class="display-6"><?= t('Algo ha ido mal') ?></h1>
    <p class="text-muted">
        <?= !empty($dbError)
            ? t('No se pudo acceder a la base de datos. Comprueba que el contenedor «db» está en marcha (docker compose ps).')
            : t('Se ha producido un error inesperado. Los detalles se han guardado en el log del servidor.') ?>
    </p>
    <a class="btn btn-primary" href="/"><?= t('Volver al dashboard') ?></a>
</div>
