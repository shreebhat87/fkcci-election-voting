<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipPaymentModel extends Model
{
    protected $table = 'membership_payments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'application_id', 'amount', 'gateway', 'gateway_order_id',
        'gateway_payment_id', 'status', 'paid_at',
    ];
    protected $useTimestamps = false;

    public function latestForApplication(int $applicationId): ?array
    {
        return $this->where('application_id', $applicationId)->orderBy('id', 'DESC')->first();
    }
}
