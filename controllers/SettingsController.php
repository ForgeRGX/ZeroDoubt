<?php
declare(strict_types=1);

final class SettingsController extends BaseController
{
    public function index(): void
    {
        $this->page([]);
    }

    public function language(): void
    {
        $lang = $this->post('language');
        if (!isset(I18n::LANGS[$lang])) {
            flash('danger', t('Idioma no válido.'));
            redirect('/settings');
        }
        User::updateLanguage(Auth::id(), $lang);
        $_SESSION['lang'] = $lang;
        I18n::set($lang);
        flash('success', t('Idioma actualizado.'));
        redirect('/settings');
    }

    public function password(): void
    {
        $user = User::findById(Auth::id()) ?? $this->notFound();
        $username = (string) $user['username'];
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        if (LoginAttempt::tooMany($ip, $username)) {
            $this->page(['password_form' => t('Demasiados intentos fallidos. Espera unos 15 minutos antes de volver a intentarlo.')], 429);
            return;
        }

        $errors = [];
        if (!password_verify($current, $user['password_hash'])) {
            LoginAttempt::record($ip, $username);
            $errors['current_password'] = t('La contraseña actual no es correcta.');
        }
        if (strlen($new) < 8) {
            $errors['new_password'] = t('La contraseña debe tener al menos 8 caracteres.');
        } elseif (strlen($new) > 72) {
            $errors['new_password'] = t('La contraseña no puede superar los 72 caracteres.');
        } elseif (mb_strtolower($new) === mb_strtolower($username)) {
            $errors['new_password'] = t('La contraseña no puede ser igual al nombre de usuario.');
        } elseif ($new === $current) {
            $errors['new_password'] = t('La nueva contraseña debe ser distinta de la actual.');
        }
        if (!isset($errors['new_password']) && $new !== $confirm) {
            $errors['new_password_confirm'] = t('Las contraseñas no coinciden.');
        }
        if ($errors) {
            $this->page($errors, 422);
            return;
        }

        User::updateHash((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        LoginAttempt::clear($ip, $username);
        session_regenerate_id(true);
        flash('success', t('Contraseña actualizada correctamente.'));
        redirect('/settings');
    }

    private function page(array $errors, int $status = 200): void
    {
        $this->render('settings/index', [
            'title'    => t('Configuración'),
            'errors'   => $errors,
            'username' => Auth::name(),
            'lang'     => I18n::lang(),
        ], $status);
    }
}
