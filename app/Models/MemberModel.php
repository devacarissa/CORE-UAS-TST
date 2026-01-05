<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table            = 'members';
    protected $primaryKey       = 'id';
    // Perhatikan: 'name' dan 'email' sudah kita masukkan di sini
    protected $allowedFields    = ['partner_id', 'ext_user_id', 'name', 'email', 'point_balance', 'tier_level'];
    protected $useTimestamps    = true;
}
