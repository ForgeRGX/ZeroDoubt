SET NAMES utf8mb4;

-- Usuarios de la aplicación. Cada usuario solo ve sus propios datos.
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    language      CHAR(2) NOT NULL DEFAULT 'es',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_users_username UNIQUE (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Intentos de login fallidos (limitación de fuerza bruta).
CREATE TABLE IF NOT EXISTS login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip         VARCHAR(45) NOT NULL,
    username   VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_login_attempts_ip (ip, created_at),
    KEY idx_login_attempts_user (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Categorías de ingresos y de gastos (propias de cada usuario).
-- default_withholding: retención por defecto (%) que se propone al crear un ingreso de esta categoría.
CREATE TABLE IF NOT EXISTS categories (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    type                ENUM('income','expense') NOT NULL,
    name                VARCHAR(100) NOT NULL,
    color               CHAR(7) NOT NULL DEFAULT '#64748b',
    icon                VARCHAR(40) NOT NULL DEFAULT 'tag',
    default_withholding DECIMAL(5,2) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_categories_user_type_name UNIQUE (user_id, type, name),
    CONSTRAINT fk_categories_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Reglas de movimientos recurrentes (p. ej. «Gimnasio, 50 € cada mes»).
-- Cada vez que vence una repetición se crea un movimiento «pendiente de validar»
-- (o ya confirmado, si auto_confirm = 1). next_due_date es NULL cuando la regla ha terminado.
CREATE TABLE IF NOT EXISTS recurring_rules (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    category_id     INT UNSIGNED NOT NULL,
    type            ENUM('income','expense') NOT NULL,
    description     VARCHAR(150) NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    gross_amount    DECIMAL(12,2) NULL,
    withholding_pct DECIMAL(5,2) NULL,
    withholding_amount DECIMAL(12,2) NULL,
    frequency       ENUM('weekly','monthly') NOT NULL DEFAULT 'monthly',
    interval_count  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    start_date      DATE NOT NULL,
    end_date        DATE NULL,
    next_due_date   DATE NULL,
    auto_confirm    TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    notes           TEXT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recurring_user (user_id, is_active, next_due_date),
    CONSTRAINT fk_recurring_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_recurring_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_recurring_amount CHECK (amount > 0),
    CONSTRAINT chk_recurring_interval CHECK (interval_count >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Movimientos (ingresos y gastos).
--   amount            = importe real: neto en ingresos, total en gastos.
--   gross_amount      = bruto (solo ingresos, opcional); withholding_* = retención asociada.
--   status            = confirmed (cuenta en gráficas) | pending (generado por una regla, falta validar).
--   recurring_id/due_date = regla que lo generó y fecha prevista (única por regla: evita duplicados).
CREATE TABLE IF NOT EXISTS transactions (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id            INT UNSIGNED NOT NULL,
    category_id        INT UNSIGNED NOT NULL,
    type               ENUM('income','expense') NOT NULL,
    description        VARCHAR(150) NOT NULL,
    transaction_date   DATE NOT NULL,
    amount             DECIMAL(12,2) NOT NULL,
    gross_amount       DECIMAL(12,2) NULL,
    withholding_pct    DECIMAL(5,2) NULL,
    withholding_amount DECIMAL(12,2) NULL,
    status             ENUM('confirmed','pending') NOT NULL DEFAULT 'confirmed',
    recurring_id       INT UNSIGNED NULL,
    due_date           DATE NULL,
    notes              TEXT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_transactions_recurring_due UNIQUE (recurring_id, due_date),
    KEY idx_transactions_user_status_date (user_id, status, transaction_date),
    KEY idx_transactions_user_type_date (user_id, type, transaction_date),
    KEY idx_transactions_user_category (user_id, category_id),
    CONSTRAINT fk_transactions_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_recurring FOREIGN KEY (recurring_id)
        REFERENCES recurring_rules (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_transactions_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Presupuesto mensual por categoría de gasto.
CREATE TABLE IF NOT EXISTS budgets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT uq_budgets_user_category UNIQUE (user_id, category_id),
    CONSTRAINT fk_budgets_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_budgets_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_budgets_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
