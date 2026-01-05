<?php

namespace App\Models;

use CodeIgniter\Model;

class UserVoucherModel extends Model
{
    protected $table            = 'user_vouchers';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['member_id', 'reward_id', 'voucher_code', 'status', 'redeemed_at'];
    protected $useTimestamps    = true;
}
