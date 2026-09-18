<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Distinguishes *where* a 'members' import batch came from (Excel upload
 * vs Zoho CRM sync) — `type` alone only separates members-data imports
 * from photo imports, not the two different members-data sources.
 */
class AddSourceToImportBatchesTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('import_batches', [
            'source' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'type',
            ],
        ]);

        // Backfill existing rows so the column is never ambiguously NULL
        // for a members-type batch going forward.
        $this->db->table('import_batches')
            ->where('type', 'members')
            ->set('source', 'excel')
            ->update();
    }

    public function down()
    {
        $this->forge->dropColumn('import_batches', 'source');
    }
}
