<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipApplicationModel extends Model
{
    protected $table = 'membership_applications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'application_ref', 'status',
        'admission_fee', 'subscription_fee', 'gst_amount', 'total_fee',
        'proposed_by', 'proposed_by_id_card_no', 'seconded_by', 'seconded_by_id_card_no',
        'committee_presented_on', 'committee_recommended_by', 'committee_recommended_on', 'committee_rejection_reason',
        'managing_committee_presented_on', 'managing_committee_decided_by', 'managing_committee_decided_on', 'managing_committee_rejection_reason',
        'registered_on', 'certificate_issued_on', 'submitted_at',
    ];
    protected $useTimestamps = true;

    /** A short, unguessable-enough reference an applicant can use to check status/pay without an account. */
    public function generateRef(): string
    {
        do {
            $ref = 'FKCCI-' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->where('application_ref', $ref)->first());

        return $ref;
    }

    public function findByRef(string $ref): ?array
    {
        return $this->where('application_ref', trim($ref))->first();
    }

    public function withCompany(int $id): ?array
    {
        $app = $this->find($id);
        if (! $app) {
            return null;
        }

        $app['company'] = model(CompanyModel::class)->find($app['company_id']);

        return $app;
    }

    public function withCompanyByRef(string $ref): ?array
    {
        $app = $this->findByRef($ref);
        if (! $app) {
            return null;
        }

        $app['company'] = model(CompanyModel::class)->find($app['company_id']);

        return $app;
    }

    // ---------- Workflow transitions (mirrors the paper form's own stages) ----------

    public function presentToCommittee(int $id, string $presentedOn, ?string $proposedBy, ?string $proposedByIdCard, ?string $secondedBy, ?string $secondedByIdCard): bool
    {
        return $this->update($id, [
            'status' => 'committee_review',
            'committee_presented_on' => $presentedOn,
            'proposed_by' => $proposedBy,
            'proposed_by_id_card_no' => $proposedByIdCard,
            'seconded_by' => $secondedBy,
            'seconded_by_id_card_no' => $secondedByIdCard,
        ]);
    }

    public function recommendByCommittee(int $id, int $adminUserId): bool
    {
        return $this->update($id, [
            'status' => 'committee_recommended',
            'committee_recommended_by' => $adminUserId,
            'committee_recommended_on' => date('Y-m-d H:i:s'),
        ]);
    }

    public function rejectByCommittee(int $id, string $reason, int $adminUserId): bool
    {
        return $this->update($id, [
            'status' => 'committee_rejected',
            'committee_rejection_reason' => $reason,
            'committee_recommended_by' => $adminUserId,
            'committee_recommended_on' => date('Y-m-d H:i:s'),
        ]);
    }

    public function presentToManagingCommittee(int $id, string $presentedOn): bool
    {
        return $this->update($id, [
            'status' => 'managing_committee_review',
            'managing_committee_presented_on' => $presentedOn,
        ]);
    }

    public function approveByManagingCommittee(int $id, int $adminUserId): bool
    {
        return $this->update($id, [
            'status' => 'approved',
            'managing_committee_decided_by' => $adminUserId,
            'managing_committee_decided_on' => date('Y-m-d H:i:s'),
        ]);
    }

    public function rejectByManagingCommittee(int $id, string $reason, int $adminUserId): bool
    {
        return $this->update($id, [
            'status' => 'rejected',
            'managing_committee_rejection_reason' => $reason,
            'managing_committee_decided_by' => $adminUserId,
            'managing_committee_decided_on' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markRegistered(int $id, string $registeredOn, string $certificateIssuedOn): bool
    {
        return $this->update($id, [
            'registered_on' => $registeredOn,
            'certificate_issued_on' => $certificateIssuedOn,
        ]);
    }
}
