<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds EVM vote-confirmation tracking: a member's slip is proof of
 * eligibility, not proof they actually cast a ballot at the EVM. Members
 * surrender the slip after voting at the EVM, an exit-desk operator (or
 * admin) scans its QR to confirm that, and this stamps `voted_at` /
 * `voted_by` / `exit_desk_no` on the existing vote row.
 *
 * This is deliberately NOT a new `votes.status` value. `status` drives
 * `active_company_id` (see CreateVotesTableMigration), which enforces
 * one-issued-slip-per-company — a slip must stay 'issued' after EVM
 * confirmation, or a second slip could be issued for the same company
 * once the first is confirmed. EVM confirmation and slip validity are
 * independent facts, so they get independent columns.
 */
class AddEvmVoteConfirmationTracking extends Migration
{
    public function up()
    {
        $this->forge->addColumn('votes', [
            'voted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'voided_at'],
            'voted_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'voted_at'],
            'exit_desk_no' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'after' => 'voted_by'],
        ]);
        $this->db->query('ALTER TABLE `votes` ADD KEY `idx_votes_voted_by` (`voted_by`)');
        $this->db->query(
            'ALTER TABLE `votes` ADD CONSTRAINT `fk_votes_voted_by` FOREIGN KEY (`voted_by`) REFERENCES `users` (`id`)'
        );

        // New role for exit-desk operators, plus which desk they're assigned
        // to (mirrors `assigned_counter` for issuing operators).
        $this->db->query(
            "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'operator', 'exit_operator') NOT NULL"
        );
        $this->forge->addColumn('users', [
            'assigned_exit_desk' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'after' => 'assigned_counter'],
        ]);
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `votes` DROP FOREIGN KEY `fk_votes_voted_by`');
        $this->db->query('ALTER TABLE `votes` DROP KEY `idx_votes_voted_by`');
        $this->forge->dropColumn('votes', ['voted_at', 'voted_by', 'exit_desk_no']);

        $this->forge->dropColumn('users', 'assigned_exit_desk');
        $this->db->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'operator') NOT NULL");
    }
}
