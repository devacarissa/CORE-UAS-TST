<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'member_id'   => ['type' => 'INTEGER', 'constraint' => 11],
            'amount'      => ['type' => 'INTEGER'], // +50 atau -20
            'type'        => ['type' => 'VARCHAR', 'constraint' => 20], // EARN atau REDEEM
            'description' => ['type' => 'TEXT', 'null' => true],
            'trx_code'    => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('member_id', 'members', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
    }
}