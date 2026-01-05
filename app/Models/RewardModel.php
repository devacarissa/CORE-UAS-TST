<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardModel extends Model
{
    protected $table            = 'rewards';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['partner_id', 'reward_name', 'description', 'point_cost', 'stock', 'is_active'];
    protected $useTimestamps    = true;
}
