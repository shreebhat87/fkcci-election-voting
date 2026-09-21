<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipDocumentModel extends Model
{
    protected $table = 'membership_documents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['application_id', 'doc_type', 'file_path', 'original_filename', 'uploaded_at'];
    protected $useTimestamps = false;

    public function forApplication(int $applicationId): array
    {
        return $this->where('application_id', $applicationId)->findAll();
    }
}
