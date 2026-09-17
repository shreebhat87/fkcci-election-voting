<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberModel extends Model
{
    protected $table = 'members';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id', 'member_id', 'rfid_tag', 'name', 'designation',
        'mobile', 'email', 'photo_path',
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'company_id' => 'required|is_natural_no_zero',
        'member_id' => 'required|max_length[30]|is_unique[members.member_id,id,{id}]',
        'rfid_tag' => 'required|max_length[64]|is_unique[members.rfid_tag,id,{id}]',
        'name' => 'required|max_length[150]',
        'designation' => 'permit_empty|max_length[100]',
        'mobile' => 'permit_empty|max_length[20]',
        'email' => 'permit_empty|valid_email|max_length[150]',
    ];

    /**
     * Look up a member by their scanned RFID tag, joined with company name.
     * This is the hot path hit on every counter scan.
     */
    public function findByRfid(string $rfidTag): ?array
    {
        return $this->select('members.*, companies.name as company_name')
            ->join('companies', 'companies.id = members.company_id')
            ->where('members.rfid_tag', trim($rfidTag))
            ->first();
    }

    public function findByMemberIdWithCompany(string $memberId): ?array
    {
        return $this->select('members.*, companies.name as company_name')
            ->join('companies', 'companies.id = members.company_id')
            ->where('members.member_id', trim($memberId))
            ->first();
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
