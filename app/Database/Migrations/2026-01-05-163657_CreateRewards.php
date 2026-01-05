<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRewards extends Migration
{
    public function up()
    {
        // 1. Tabel REWARDS (Barang yang bisa ditukar)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'partner_id'    => ['type' => 'INTEGER', 'constraint' => 5],
            'reward_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'point_cost'    => ['type' => 'INTEGER'],
            'stock'         => ['type' => 'INTEGER', 'default' => 0],
            'is_active'     => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rewards');

        // 2. Tabel USER_VOUCHERS (Kupon milik user)
        $this->forge->addField([
            'id'            => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'member_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'reward_id'     => ['type' => 'INTEGER', 'constraint' => 11],
            'voucher_code'  => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'UNUSED'], // UNUSED, USED
            'redeemed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('member_id', 'members', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reward_id', 'rewards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_vouchers');
    }

    public function down()
    {
        $this->forge->dropTable('user_vouchers');
        $this->forge->dropTable('rewards');
    }
}