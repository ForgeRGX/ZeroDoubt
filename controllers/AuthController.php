<?php
declare(strict_types=1);

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $this->renderLogin(['username' => ''], []);
    }

    public function login(): void
    {
        $username = $this->post('username');
        $password = (string) ($_POST['password'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if (LoginAttempt::tooMany($ip, $username)) {
            $this->renderLogin(
                ['username' => $username],
                ['form' => t('Demasiados intentos fallidos. Espera unos 15 minutos antes de volver a intentarlo.')],
                429
            );
            return;
        }

        $user = $username !== '' ? User::findByUsername($username) : null;
        // Se verifica siempre un hash para que el tiempo de respuesta no revele si el usuario existe
        $hash = $user['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            LoginAttempt::record($ip, $username);
            $this->renderLogin(['username' => $username], ['form' => t('Usuario o contraseña incorrectos.')], 401);
            return;
        }

        LoginAttempt::clear($ip, $username);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updateHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        User::touchLogin((int) $user['id']);
        Auth::login($user);
        redirect('/');
    }

    public function showRegister(): void
    {
        $this->guardRegistration();
        $this->renderRegister(['username' => ''], []);
    }

    public function register(): void
    {
        $this->guardRegistration();
        $username = $this->post('username');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $errors = [];
        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
            $errors['username'] = t('Entre 3 y 30 caracteres: letras, números, punto, guion y guion bajo.');
        } elseif (User::findByUsername($username) !== null) {
            $errors['username'] = t('Ese nombre de usuario ya está en uso.');
        }
        if (strlen($password) < 8) {
            $errors['password'] = t('La contraseña debe tener al menos 8 caracteres.');
        } elseif (strlen($password) > 72) {
            $errors['password'] = t('La contraseña no puede superar los 72 caracteres.');
        } elseif (mb_strtolower($password) === mb_strtolower($username)) {
            $errors['password'] = t('La contraseña no puede ser igual al nombre de usuario.');
        }
        if (!isset($errors['password']) && $password !== $confirm) {
            $errors['password_confirm'] = t('Las contraseñas no coinciden.');
        }
        if ($errors) {
            $this->renderRegister(['username' => $username], $errors, 422);
            return;
        }

        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $id = User::create($username, password_hash($password, PASSWORD_DEFAULT), I18n::lang());
            // Las categorías por defecto se crean para el nuevo usuario (se pueden editar o borrar)
            Category::createDefaults($id);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), '1062')) {
                $this->renderRegister(['username' => $username], ['username' => t('Ese nombre de usuario ya está en uso.')], 422);
                return;
            }
            throw $e;
        }

        Auth::login(['id' => $id, 'username' => $username, 'language' => I18n::lang()]);
        flash('success', t('¡Cuenta creada! Ya puedes registrar tus ingresos y gastos.'));
        redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', t('Has cerrado sesión.'));
        redirect('/login');
    }

    private function registrationOpen(): bool
    {
        return (bool) config('allow_registration') || User::count() === 0;
    }

    private function guardRegistration(): void
    {
        if (!$this->registrationOpen()) {
            flash('danger', t('El registro de nuevos usuarios está desactivado.'));
            redirect('/login');
        }
    }

    private function renderLogin(array $item, array $errors, int $status = 200): void
    {
        $this->render('auth/login', [
            'title' => t('Iniciar sesión'), 'item' => $item, 'errors' => $errors,
            'canRegister' => $this->registrationOpen(),
        ], $status);
    }

    private function renderRegister(array $item, array $errors, int $status = 200): void
    {
        $this->render('auth/register', ['title' => t('Crear cuenta'), 'item' => $item, 'errors' => $errors], $status);
    }
}
