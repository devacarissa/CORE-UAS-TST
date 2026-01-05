<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartners extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INTEGER', 'constraint' => 5, 'auto_increment' => true],
            'partner_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'api_key'      => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'is_active'    => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('partners');
    }

    public function down()
    {
        $this->forge->dropTable('partners');
    }
}