<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportBatchModel extends Model
{
    protected $table = 'import_batches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'type', 'filename', 'row_count', 'valid_count', 'error_count',
        'details', 'imported_by', 'imported_at',
    ];
    protected $useTimestamps = false;

    public function recent(string $type, int $limit = 10): array
    {
        return $this->where('type', $type)
            ->orderBy('imported_at', 'DESC')
            ->findAll($limit);
    }
}
