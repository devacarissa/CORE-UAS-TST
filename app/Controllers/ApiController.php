<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\PartnerModel;
use App\Models\MemberModel;
use App\Models\TransactionModel;

class ApiController extends BaseController
{
    use ResponseTrait;

    // 1. REGISTER PARTNER (Daftar Mitra)
    public function registerPartner()
    {
        $model = new PartnerModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->name)) {
            return $this->fail('Nama partner wajib diisi', 400);
        }

        $apiKey = $model->generateApiKey();

        $data = [
            'partner_name' => $json->name,
            'api_key'      => $apiKey,
            'is_active'    => 1
        ];

        if ($model->insert($data)) {
            return $this->respondCreated([
                'status' => 201,
                'message' => 'Partner berhasil didaftarkan',
                'data' => [
                    'partner_name' => $json->name,
                    'api_key' => $apiKey
                ]
            ]);
        }
        return $this->failServerError('Gagal menyimpan partner');
    }

    // 2. CREATE MEMBER (Daftar Member Baru)
    public function createMember()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->api_key) || !isset($json->user_id)) {
            return $this->fail('API Key dan User ID wajib diisi', 400);
        }

        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) {
            return $this->fail('API Key tidak valid', 401);
        }

        $existingMember = $memberModel->where('partner_id', $partner['id'])
                                      ->where('ext_user_id', $json->user_id)
                                      ->first();

        if ($existingMember) {
            return $this->fail('Member sudah terdaftar', 409);
        }

        $newMember = [
            'partner_id'  => $partner['id'],
            'ext_user_id' => $json->user_id,
            'name'        => $json->name ?? null,
            'email'       => $json->email ?? null,
            'point_balance' => 0,
            'tier_level'  => 'BRONZE'
        ];

        $memberModel->insert($newMember);

        return $this->respondCreated([
            'status' => 201,
            'message' => 'Member berhasil dibuat',
            'data' => $newMember
        ]);
    }

    // 3. ADD POINTS (Tambah Poin)
    public function addPoints()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $trxModel = new TransactionModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->api_key) || !isset($json->user_id) || !isset($json->amount)) {
            return $this->fail('Data tidak lengkap', 400);
        }

        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key salah', 401);

        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $json->user_id)
                              ->first();
        if (!$member) return $this->fail('Member tidak ditemukan', 404);

        // Update Saldo
        $newBalance = $member['point_balance'] + $json->amount;
        $memberModel->update($member['id'], ['point_balance' => $newBalance]);

        // Catat Log
        $trxModel->insert([
            'member_id'   => $member['id'],
            'amount'      => $json->amount,
            'type'        => 'EARN',
            'description' => $json->description ?? 'Topup Poin',
            'trx_code'    => 'TRX-' . time() . rand(100,999),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        return $this->respond([
            'status' => 200,
            'message' => 'Poin berhasil ditambahkan',
            'current_balance' => $newBalance
        ]);
    }

    // 4. GET BALANCE (Cek Saldo - FITUR BARU)
    public function getBalance()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $json = $this->request->getJSON();

        // Validasi
        if (!$json || !isset($json->api_key) || !isset($json->user_id)) {
            return $this->fail('API Key dan User ID wajib diisi', 400);
        }

        // Cek Partner
        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key salah', 401);

        // Cek Member
        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $json->user_id)
                              ->first();
        
        if (!$member) return $this->fail('Member tidak ditemukan', 404);

        // Kembalikan Data Saldo
        return $this->respond([
            'status' => 200,
            'message' => 'Data saldo ditemukan',
            'data' => [
                'user_id' => $member['ext_user_id'],
                'name' => $member['name'],
                'point_balance' => (int)$member['point_balance'],
                'tier_level' => $member['tier_level']
            ]
        ]);
    }

    public function redeemPoints()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $trxModel = new TransactionModel();
        $json = $this->request->getJSON();

        // Validasi input
        if (!$json || !isset($json->api_key) || !isset($json->user_id) || !isset($json->amount)) {
            return $this->fail('Data tidak lengkap (api_key, user_id, amount wajib ada)', 400);
        }

        // Cek Partner
        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key salah', 401);

        // Cek Member
        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $json->user_id)
                              ->first();
        if (!$member) return $this->fail('Member tidak ditemukan', 404);

        // --- CEK SALDO CUKUP ATAU TIDAK ---
        if ($member['point_balance'] < $json->amount) {
            return $this->fail('Poin tidak cukup. Saldo saat ini: ' . (int)$member['point_balance'], 400);
        }

        // Update Saldo (Kurangi Poin)
        $newBalance = $member['point_balance'] - $json->amount;
        $memberModel->update($member['id'], ['point_balance' => $newBalance]);

        // Catat di Riwayat Transaksi sebagai REDEEM
        $trxModel->insert([
            'partner_id'  => $partner['id'],
            'member_id'   => $member['id'],
            'amount'      => $json->amount,
            'type'        => 'REDEEM', // Kategori Tukar
            'description' => $json->description ?? 'Tukar Voucher',
            'trx_code'    => 'RD-'.time().rand(10,99),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        return $this->respond([
            'status' => 200,
            'message' => 'Poin berhasil ditukar dengan voucher',
            'remaining_balance' => $newBalance
        ]);
    }
}
