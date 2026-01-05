<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMembers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'partner_id'    => ['type' => 'INTEGER', 'constraint' => 5],
            'ext_user_id'   => ['type' => 'VARCHAR', 'constraint' => 100], // ID dari sistem Parkir/Kantin
            
            // --- DATA TAMBAHAN (REVISI) ---
            'name'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // -----------------------------

            'point_balance' => ['type' => 'INTEGER', 'default' => 0],
            'tier_level'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'BRONZE'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['partner_id', 'ext_user_id']); 
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('members');
    }

    public function down()
    {
        $this->forge->dropTable('members');
    }
}