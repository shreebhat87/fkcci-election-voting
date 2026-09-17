<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'username', 'password_hash', 'role', 'name', 'assigned_counter',
        'active', 'last_login_at',
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'username' => 'required|max_length[100]|is_unique[users.username,id,{id}]',
        'role' => 'required|in_list[admin,operator]',
        'name' => 'required|max_length[150]',
        'assigned_counter' => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
    ];

    public function findActiveByUsername(string $username): ?array
    {
        return $this->where('username', trim($username))
            ->where('active', 1)
            ->first();
    }

    public function touchLastLogin(int $id): void
    {
        $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }
}
