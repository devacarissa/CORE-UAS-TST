<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['member_id', 'amount', 'type', 'description', 'trx_code', 'created_at'];
    protected $useTimestamps    = false; // Kita atur manual nanti
}
