<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table = 'members';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'application_id', 'member_id', 'rfid_tag', 'name', 'designation',
        'mobile', 'email', 'photo_path', 'is_election_rep',
    ];
    protected $useTimestamps = true;

    /**
     * member_id/rfid_tag are 'permit_empty' at the Model level, not
     * 'required': a representative captured via membership self-registration
     * (see MembershipController) exists before either is assigned — both
     * are only ever set later, by the admin register/RFID-issuance action
     * (see MembershipRegistrationController). The Excel/Zoho import path
     * (MasterDataController) still guarantees both are non-empty itself,
     * before it ever calls insert() — this permit_empty only widens what
     * the *self-registration* path is allowed to do.
     */
    protected $validationRules = [
        'company_id' => 'required|is_natural_no_zero',
        'member_id' => 'permit_empty|max_length[30]|is_unique[members.member_id,id,{id}]',
        'rfid_tag' => 'permit_empty|max_length[64]|is_unique[members.rfid_tag,id,{id}]',
        'name' => 'required|max_length[150]',
        'designation' => 'permit_empty|max_length[100]',
        'mobile' => 'permit_empty|max_length[20]',
        'email' => 'permit_empty|valid_email|max_length[150]',
    ];

    /**
     * Look up a member by their scanned RFID tag, joined with company name.
     * This is the hot path hit on every counter scan.
     *
     * `is_election_rep = 1` matters now that a company can have up to 3
     * representatives via membership self-registration (large/medium and
     * association members) — only the first 2 are flagged as election
     * reps (see MembershipController). Without this filter, one-vote-
     * per-company would still be enforced (the constraint is on
     * company_id, not member_id), but a 3rd representative's tag could
     * cast the company's one vote instead of one of the two actually
     * designated to. Every legacy Excel/Zoho-imported row defaults to
     * is_election_rep = 1, so this changes nothing for the existing
     * 2-rep-only election dataset.
     */
    public function findByRfid(string $rfidTag): ?array
    {
        return $this->select('members.*, companies.name as company_name')
            ->join('companies', 'companies.id = members.company_id')
            ->where('members.rfid_tag', trim($rfidTag))
            ->where('members.is_election_rep', 1)
            ->first();
    }

    public function findByMemberIdWithCompany(string $memberId): ?array
    {
        return $this->select('members.*, companies.name as company_name')
            ->join('companies', 'companies.id = members.company_id')
            ->where('members.member_id', trim($memberId))
            ->first();
    }

    /** Representatives captured for a membership application, in the order they were added (oldest first — representative 1, 2, 3...). */
    public function forApplication(int $applicationId): array
    {
        return $this->where('application_id', $applicationId)->orderBy('id', 'ASC')->findAll();
    }

    /** Members that have no photo on file — for the admin coverage report. */
    public function withoutPhoto(): array
    {
        return $this->select('members.*, companies.name as company_name')
            ->join('companies', 'companies.id = members.company_id')
            ->where('members.photo_path', null)
            ->orderBy('companies.name', 'ASC')
            ->findAll();
    }
}
