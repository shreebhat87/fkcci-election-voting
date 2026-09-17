<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVotesTableMigration extends Migration
{
    /**
     * "One vote per company" is enforced by MySQL itself, not just by
     * application logic. `active_company_id` is a STORED generated column
     * that equals `company_id` while status='issued' and NULL otherwise.
     * MySQL unique indexes allow unlimited NULLs but only one of any given
     * non-NULL value, so a unique index on that column is exactly a
     * "partial unique index on issued rows" — the standard MySQL substitute
     * for Postgres' `WHERE status = 'issued'` partial index.
     *
     * This means even if two of the 10 counters' application-level checks
     * both race past the "already voted?" read at the same instant, only
     * one of the two concurrent INSERTs can succeed; the second raises a
     * duplicate-key error that VoteModel::issue() catches and turns into
     * an "already voted" result instead of a second slip.
     */
    public function up()
    {
        $this->db->query(<<<'SQL'
            CREATE TABLE `votes` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `member_id` INT UNSIGNED NOT NULL,
                `company_id` INT UNSIGNED NOT NULL,
                `counter_no` TINYINT UNSIGNED NOT NULL,
                `serial_no` VARCHAR(40) NOT NULL,
                `status` ENUM('issued', 'void') NOT NULL DEFAULT 'issued',
                `void_reason` VARCHAR(255) NULL,
                `issued_by` INT UNSIGNED NOT NULL,
                `voided_by` INT UNSIGNED NULL,
                `issued_at` DATETIME NOT NULL,
                `voided_at` DATETIME NULL,
                `active_company_id` INT UNSIGNED
                    GENERATED ALWAYS AS (IF(`status` = 'issued', `company_id`, NULL)) STORED,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_votes_serial_no` (`serial_no`),
                UNIQUE KEY `uq_votes_active_company` (`active_company_id`),
                KEY `idx_votes_member_id` (`member_id`),
                KEY `idx_votes_company_id` (`company_id`),
                KEY `idx_votes_issued_by` (`issued_by`),
                KEY `idx_votes_voided_by` (`voided_by`),
                CONSTRAINT `fk_votes_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
                CONSTRAINT `fk_votes_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
                CONSTRAINT `fk_votes_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`),
                CONSTRAINT `fk_votes_voided_by` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        SQL);
    }

    public function down()
    {
        $this->forge->dropTable('votes');
    }
}
