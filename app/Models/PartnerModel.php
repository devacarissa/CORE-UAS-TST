<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerModel extends Model
{
    protected $table            = 'partners';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['partner_name', 'api_key', 'is_active'];
    protected $useTimestamps    = true;

    // Fungsi otomatis generate API Key
    public function generateApiKey()
    {
        return bin2hex(random_bytes(32));
    }
}
