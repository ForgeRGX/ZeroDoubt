<?php
declare(strict_types=1);

/** Movimientos generados por reglas recurrentes que están pendientes de validar. */
final class PendingController extends BaseController
{
    public function index(): void
    {
        Recurring::generateDue(Auth::id());
        $this->render('pending/index', [
            'title'   => t('Pendientes de validar'),
            'rows'    => Transaction::pending(),
            'summary' => Transaction::pendingSummary(),
            'today'   => date('Y-m-d'),
        ]);
    }

    /** Valida tal cual se había previsto (misma fecha e importe). Para cambiarlos: «Revisar». */
    public function confirm(string $id): void
    {
        if (Transaction::confirmAsPlanned((int) $id)) {
            flash('success', t('Movimiento validado correctamente.'));
        } else {
            $this->notFound();
        }
        redirect('/pending');
    }

    public function confirmAll(): void
    {
        $n = Transaction::confirmAllPending();
        flash('success', t('%d movimiento(s) validado(s) con su fecha prevista.', [$n]));
        redirect('/pending');
    }

    /** Descarta esta repetición (no la cuenta ni se vuelve a generar). */
    public function skip(string $id): void
    {
        if (!Transaction::skip((int) $id)) {
            $this->notFound();
        }
        flash('success', t('Repetición omitida.'));
        redirect('/pending');
    }
}
