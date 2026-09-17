<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImportBatchesTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['members', 'photos']],
            'filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'row_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'valid_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            // JSON summary of per-row errors / unmatched files, for the
            // admin UI's import history detail — not queried on, hence TEXT.
            'details' => ['type' => 'TEXT', 'null' => true],
            'imported_by' => ['type' => 'INT', 'unsigned' => true],
            'imported_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('imported_by');
        $this->forge->addForeignKey('imported_by', 'users', 'id');
        $this->forge->createTable('import_batches');
    }

    public function down()
    {
        $this->forge->dropTable('import_batches');
    }
}
