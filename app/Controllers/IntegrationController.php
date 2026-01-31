<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\PartnerModel;
use App\Models\MemberModel;
use App\Models\TransactionModel;
use App\Models\VoucherModel; // Pastikan Model ini sudah dibuat

class IntegrationController extends BaseController
{
    use ResponseTrait;

    public function payParking()
    {
        $json = $this->request->getJSON();
        
        $userId      = $json->user_id ?? null;
        $ticketId    = $json->ticket_id ?? null;
        $initialCost = $json->amount ?? 0;
        $apiKey      = $json->api_key ?? null;
        $voucherCode = $json->voucher_code ?? null; 

        if (!$userId || !$ticketId || !$apiKey) {
            return $this->fail('Data tidak lengkap (user_id, ticket_id, api_key wajib)', 400);
        }

        $partnerModel = new PartnerModel();
        $partner = $partnerModel->where('api_key', $apiKey)->first();
        if (!$partner) {
            return $this->fail('Integrasi Gagal: API Key Mitra Parkir Salah', 401);
        }

        $memberModel = new MemberModel();
        $trxModel    = new TransactionModel();
        
        $member = $memberModel->where('partner_id', $partner['id'])
                              ->where('ext_user_id', $userId)
                              ->first();
        
        if (!$member) {
            return $this->failNotFound('Member tidak ditemukan di sistem mitra ini');
        }

        $discount = 0;
        $voucherMessage = "Tidak ada voucher";

        if ($voucherCode) {
            $voucherModel = new VoucherModel();
            $voucher = $voucherModel->where('code', $voucherCode)
                                    ->where('is_active', 1)
                                    ->first();
            
            if ($voucher) {
                $discount = $voucher['amount'];
                $voucherMessage = "Voucher " . $voucherCode . " dipasang (Hemat " . $discount . ")";
            } else {
                return $this->fail('Kode Voucher Tidak Valid / Kadaluarsa', 400);
            }
        }


        $finalCost = $initialCost - $discount;
        if ($finalCost < 0) $finalCost = 0; 

        if ($member['point_balance'] < $finalCost) {
            return $this->fail('Saldo Poin Tidak Cukup. Sisa tagihan: ' . $finalCost, 402);
        }

        $parkingDB = \Config\Database::connect('parking');
        $existingTicket = $parkingDB->table('tickets')->where('id', $ticketId)->get()->getRowArray();

        if (!$existingTicket) {
            return $this->failNotFound('Tiket Parkir Tidak Ditemukan di Database Parkir');
        }

        if ($existingTicket['status'] === 'paid') {
            return $this->fail('Gagal: Tiket ini SUDAH DIBAYAR sebelumnya.', 409); // 409 Conflict
        }

        try {
            $newBalance = $member['point_balance'];
            if ($finalCost > 0) {
                $newBalance = $member['point_balance'] - $finalCost;
                $memberModel->update($member['id'], ['point_balance' => $newBalance]);

                $trxModel->insert([
                    'member_id'   => $member['id'],
                    'amount'      => $finalCost,
                    'type'        => 'REDEEM',
                    'description' => 'Bayar Parkir (Tiket #' . $ticketId . ') ' . ($voucherCode ? '+ ' . $voucherCode : ''),
                    'trx_code'    => 'PRK-' . time() . rand(100,999),
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }

            $parkingDB = \Config\Database::connect('parking');
            $parkingDB->table('tickets')->where('id', $ticketId)->update([
                'status'     => 'paid',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Pembayaran Parkir Berhasil',
                'data' => [
                    'biaya_awal'    => $initialCost,
                    'potongan'      => $discount,
                    'bayar_poin'    => $finalCost,
                    'sisa_poin'     => $newBalance,
                    'info_voucher'  => $voucherMessage,
                    'partner'       => $partner['partner_name'],
                    'ticket_status' => 'PAID'
                ]
            ]);

        } catch (\Exception $e) {
            return $this->failServerError('Error System: ' . $e->getMessage());
        }
    }
}