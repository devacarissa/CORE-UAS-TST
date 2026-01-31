<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Setup extends Controller
{
    public function init()
    {
        $db = \Config\Database::connect('parking');
        $forge = \Config\Database::forge('parking');
        $forge->dropTable('tickets', true);

        $fields = [
            'id'          => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'user_id'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'amount_due'  => ['type' => 'INTEGER', 'constraint' => 11],
            'status'      => ['type' => 'TEXT', 'default' => 'active'], 
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ];

        $forge->addField($fields);
        $forge->addPrimaryKey('id');
        $forge->createTable('tickets');

        $data = [
            'id' => 1,
            'user_id' => 'U001',
            'amount_due' => 5000,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $db->table('tickets')->insert($data);

        echo "<h1>Sukses!</h1>";
        echo "File database <b>parking_dummy.db</b> berhasil dibuat di folder 'writable'.<br>";
        echo "Data dummy tiket ID 1 (Tagihan 5000) sudah siap.";

        $fieldsVoucher = [
            'id'          => ['type' => 'INTEGER', 'constraint' => 11, 'auto_increment' => true],
            'code'        => ['type' => 'TEXT'],
            'amount'      => ['type' => 'INTEGER'],
            'is_active'   => ['type' => 'INTEGER', 'default' => 1],
        ];

        $forge->addField($fieldsVoucher);
        $forge->addPrimaryKey('id');
        $forge->createTable('vouchers');

        $db->table('vouchers')->insertBatch([
            ['code' => 'DISKON2000', 'amount' => 2000, 'is_active' => 1],
            ['code' => 'GRATISPARKIR', 'amount' => 10000, 'is_active' => 1],
        ]);
        
        echo "Setup Selesai! Tabel Tickets & Vouchers siap.";
    
    }
}