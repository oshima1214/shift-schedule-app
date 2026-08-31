-- シフト表作成アプリ テーブル定義
-- 実際の作成/変更は fuel/app/migrations 配下のマイグレーションで行う。
-- このファイルは現在のスキーマの参照用。

CREATE TABLE departments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_departments_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employees (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at    DATETIME NULL,
  updated_at    DATETIME NULL,
  deleted_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_employees_email (email),
  KEY idx_employees_deleted_at (deleted_at),
  CONSTRAINT fk_employees_department FOREIGN KEY (department_id) REFERENCES departments (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE shift_requests (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id INT UNSIGNED NOT NULL,
  work_date   DATE NOT NULL,
  start_time  TIME NOT NULL,
  end_time    TIME NOT NULL,
  status      ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
  note        VARCHAR(255) NULL,
  created_at  DATETIME NULL,
  updated_at  DATETIME NULL,
  deleted_at  DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_shift_requests_employee_date (employee_id, work_date),
  KEY idx_shift_requests_deleted_at (deleted_at),
  CONSTRAINT fk_shift_requests_employee FOREIGN KEY (employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
