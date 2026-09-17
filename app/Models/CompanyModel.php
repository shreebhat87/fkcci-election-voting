<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'name' => 'required|max_length[255]',
    ];

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
