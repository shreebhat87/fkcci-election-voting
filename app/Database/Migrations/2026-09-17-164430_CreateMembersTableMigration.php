<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMembersTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            // Human-readable ID from the master data, e.g. FKCCI-001A.
            'member_id' => ['type' => 'VARCHAR', 'constraint' => 30],
            'rfid_tag' => ['type' => 'VARCHAR', 'constraint' => 64],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            // Optional — set by the separate bulk photo upload, never by the
            // Excel import. Relative path under writable/uploads/photos/.
            'photo_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('member_id');
        $this->forge->addUniqueKey('rfid_tag');
        $this->forge->addKey('company_id');
        $this->forge->addForeignKey('company_id', 'companies', 'id', '', 'CASCADE');
        $this->forge->createTable('members');
    }

    public function down()
    {
        $this->forge->dropTable('members');
    }
}
