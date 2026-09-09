-- シフト表作成アプリ テーブル定義（DB設計書に対応）
-- 実際の作成/変更は fuel/app/migrations 配下のマイグレーションで行う。
-- このファイルは現在のスキーマの参照用。

-- 部署
CREATE TABLE `departments` (
  `id`         int NOT NULL AUTO_INCREMENT,                       -- 部署のid、主キー
  `name`       varchar(100) NOT NULL,                             -- 部署名（ホール、キッチンなど）
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,      -- 作成日時
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- 最終更新日時
  `deleted_at` timestamp NULL DEFAULT NULL,                       -- 削除日時、nullでなければ画面に表示されない
  PRIMARY KEY (`id`),
  KEY `idx_departments_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 従業員
CREATE TABLE `employees` (
  `id`              int NOT NULL AUTO_INCREMENT,                  -- 従業員のid、主キー
  `department_id`   int NOT NULL,                                 -- 所属する部署のid
  `name`            varchar(100) NOT NULL,                        -- 氏名
  `email`           varchar(255) NOT NULL,                        -- ログインID兼用、UNIQUE制約
  `password_hash`   varchar(255) NOT NULL,                        -- ハッシュ化したパスワード、平文では保存しない
  `failed_login_count` int NOT NULL DEFAULT 0,                    -- 連続したログイン失敗回数、成功またはロック時に0へ戻す
  `locked_until`    datetime DEFAULT NULL,                        -- ログインを受け付けない期限、nullならロックなし
  `employment_type` char(10) NOT NULL DEFAULT 'part_time',        -- 雇用形態 part_time:アルバイト part:パート
  `role`            char(10) NOT NULL DEFAULT 'employee',         -- 権限 employee:従業員 admin:管理者
  `created_at`      timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_employees_email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `idx_employees_deleted_at` (`deleted_at`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- シフト希望
CREATE TABLE `shift_requests` (
  `id`          int NOT NULL AUTO_INCREMENT,                      -- シフト希望のid、主キー
  `employee_id` int NOT NULL,                                     -- 提出した従業員のid
  `work_date`   date NOT NULL,                                    -- 希望勤務日
  `start_time`  time NOT NULL,                                    -- 開始時刻
  `end_time`    time NOT NULL,                                    -- 終了時刻
  `status`      char(10) NOT NULL DEFAULT 'requested',            -- 状態 requested:希望中 approved:確定 rejected:却下
  `reject_reason` varchar(255) DEFAULT NULL,                      -- 却下理由、却下以外の状態ではnull
  `created_at`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`  timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `idx_shift_requests_date_employee` (`work_date`, `employee_id`),
  KEY `idx_shift_requests_deleted_at` (`deleted_at`),
  CONSTRAINT `shift_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
