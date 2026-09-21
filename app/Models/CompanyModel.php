<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name', 'address', 'phone', 'mobile', 'email', 'website', 'year_established',
        'nature_of_business', 'business_scale', 'product_description', 'annual_turnover',
        'gstin', 'msme_registration_no', 'pan', 'company_registration_no', 'ie_code',
        'professional_institute_membership_no', 'bankers_name', 'membership_category',
        'patron_tier', 'membership_no', 'membership_status',
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'name' => 'required|max_length[255]',
    ];

    /** Assigns the next sequential number for a membership_no prefix (e.g. "SSO" -> "SSO-1327"). See CreateMembershipManagementSchema for the scheme this follows. */
    public function nextMembershipNo(string $prefix): string
    {
        $like = $prefix . '-%';
        $last = $this->select('membership_no')
            ->like('membership_no', $like, 'none')
            ->orderBy('id', 'DESC')
            ->first();

        $lastSeq = 0;
        if ($last && preg_match('/-(\d+)$/', (string) $last['membership_no'], $m)) {
            $lastSeq = (int) $m[1];
        }

        return sprintf('%s-%d', $prefix, $lastSeq + 1);
    }

    /**
     * Find a company by exact name, or create it. Used by the Excel import,
     * where the company column is free text and the same company name may
     * repeat across both of its member rows.
     */
    public function findOrCreateByName(string $name): array
    {
        $name = trim($name);
        $existing = $this->where('name', $name)->first();
        if ($existing) {
            return $existing;
        }

        $id = $this->insert(['name' => $name], true);

        return $this->find($id);
    }
}
