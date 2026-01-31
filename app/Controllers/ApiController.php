<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\PartnerModel;
use App\Models\MemberModel;
use App\Models\TransactionModel;
use App\Models\VoucherModel;

class ApiController extends BaseController
{
    use ResponseTrait;

    // --- 1. LOGIN / AUTHENTICATION ---
    public function login()
    {
        $partnerModel = new PartnerModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->api_key)) {
            return $this->fail('API Key diperlukan untuk login', 400);
        }

        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('Authentication Gagal: API Key tidak valid', 401);

        return $this->respond([
            'status' => 200,
            'message' => 'Authentication Berhasil',
            'partner' => $partner['partner_name']
        ]);
    }

    // --- 2. REGISTER PARTNER (Daftar Mitra) ---
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
                'data' => ['partner_name' => $json->name, 'api_key' => $apiKey]
            ]);
        }
        return $this->failServerError('Gagal menyimpan partner');
    }

    // --- 3. CREATE MEMBER (Daftar Member Baru) ---
    public function createMember()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->api_key) || !isset($json->user_id)) {
            return $this->fail('API Key dan User ID wajib diisi', 400);
        }

        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key tidak valid', 401);

        $existingMember = $memberModel->where('partner_id', $partner['id'])
                                      ->where('ext_user_id', $json->user_id)
                                      ->first();

        if ($existingMember) return $this->fail('Member sudah terdaftar', 409);

        $newMember = [
            'partner_id'    => $partner['id'],
            'ext_user_id'   => $json->user_id,
            'name'          => $json->name ?? null,
            'email'         => $json->email ?? null,
            'point_balance' => 0,
            'tier_level'    => 'BRONZE'
        ];

        $memberModel->insert($newMember);
        return $this->respondCreated(['status' => 201, 'message' => 'Member berhasil dibuat', 'data' => $newMember]);
    }

    // --- 4. ADD POINTS (Tambah Poin) ---
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

        $newBalance = $member['point_balance'] + $json->amount;
        $memberModel->update($member['id'], ['point_balance' => $newBalance]);

        $trxModel->insert([
            'member_id'   => $member['id'],
            'amount'      => $json->amount,
            'type'        => 'EARN',
            'description' => $json->description ?? 'Topup Poin',
            'trx_code'    => 'TRX-' . time() . rand(100,999),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        return $this->respond(['status' => 200, 'message' => 'Poin berhasil ditambahkan', 'current_balance' => $newBalance]);
    }

    // --- 5. GET BALANCE (Cek Saldo) ---
    public function getBalance()
    {
        $partnerModel = new PartnerModel();
        $memberModel = new MemberModel();
        $json = $this->request->getJSON();

        if (!$json || !isset($json->api_key) || !isset($json->user_id)) {
            return $this->fail('API Key dan User ID wajib diisi', 400);
        }

        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key salah', 401);

        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $json->user_id)
                              ->first();
        
        if (!$member) return $this->fail('Member tidak ditemukan', 404);

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

    // --- 6. REDEEM POINTS (Tukar Poin) ---
    public function redeemPoints()
    {
        $partnerModel = new PartnerModel();
        $memberModel  = new MemberModel();
        $trxModel     = new TransactionModel();
        $voucherModel = new \App\Models\VoucherModel(); // Panggil model voucher

        $json = $this->request->getJSON();

        // 1. Validasi Input Dasar
        if (!$json || !isset($json->api_key) || !isset($json->user_id)) {
            return $this->fail('Data tidak lengkap (api_key, user_id wajib)', 400);
        }

        // 2. Validasi Partner & Member
        $partner = $partnerModel->where('api_key', $json->api_key)->first();
        if (!$partner) return $this->fail('API Key salah', 401);

        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $json->user_id)
                              ->first();
        if (!$member) return $this->fail('Member tidak ditemukan', 404);


        $deductionAmount = 0;
        $description = "";

        if (isset($json->voucher_code)) {
            $voucher = $voucherModel->where('code', $json->voucher_code)->first();
            
            if (!$voucher) {
                return $this->fail('Kode Voucher tidak ditemukan', 404);
            }

            $deductionAmount = $voucher['amount']; 
            $description = "Tukar Voucher: " . $voucher['code'];

        } elseif (isset($json->amount)) {
            $deductionAmount = $json->amount;
            $description = $json->description ?? "Redeem Poin Manual";

        } else {
            return $this->fail('Harus menyertakan voucher_code ATAU amount', 400);
        }

        if ($member['point_balance'] < $deductionAmount) {
            return $this->fail('Poin tidak cukup. Butuh: ' . $deductionAmount . ', Punya: ' . (int)$member['point_balance'], 400);
        }

        $newBalance = $member['point_balance'] - $deductionAmount;
        $memberModel->update($member['id'], ['point_balance' => $newBalance]);

        $trxModel->insert([
            'member_id'   => $member['id'],
            'amount'      => $deductionAmount,
            'type'        => 'REDEEM',
            'description' => $description,
            'trx_code'    => 'RD-' . time() . rand(10,99),
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        return $this->respond([
            'status' => 200,
            'message' => 'Redeem Berhasil',
            'redeem_type' => isset($json->voucher_code) ? 'VOUCHER' : 'CASH',
            'deducted_points' => $deductionAmount,
            'remaining_balance' => $newBalance
        ]);
    }
    // --- 7. GET LIST (Untuk Dashboard/Mitra) ---
    public function listMembers()
    {
        $memberModel = new MemberModel();
        return $this->respond(['status' => 200, 'data' => $memberModel->findAll()]);
    }

    public function listPartners()
    {
        $partnerModel = new PartnerModel();
        return $this->respond(['status' => 200, 'data' => $partnerModel->findAll()]);
    }

    // --- 8. GET HISTORY (Riwayat Transaksi) ---
    public function getHistory($userId = null)
    {
        $memberModel = new MemberModel();
        $trxModel = new TransactionModel();

        $member = $memberModel->where('ext_user_id', $userId)->first();
        if (!$member) return $this->fail('Member tidak ditemukan', 404);

        $history = $trxModel->where('member_id', $member['id'])->findAll();
        return $this->respond(['status' => 200, 'user' => $member['name'], 'history' => $history]);
    }
}
