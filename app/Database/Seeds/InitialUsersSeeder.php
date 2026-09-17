<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Seeds one admin account and one operator account per counter (1-10).
 * Passwords/PINs are randomly generated and printed once to the console —
 * nothing predictable ships in the repo. Re-running this seeder is safe:
 * it skips any username that already exists rather than duplicating or
 * silently resetting credentials.
 */
class InitialUsersSeeder extends Seeder
{
    public function run()
    {
        $created = [];

        $created[] = $this->createUserIfMissing(
            username: 'admin',
            role: 'admin',
            name: 'Election Admin',
            assignedCounter: null,
            passwordLength: 12
        );

        for ($counter = 1; $counter <= 10; $counter++) {
            $created[] = $this->createUserIfMissing(
                username: "counter{$counter}",
                role: 'operator',
                name: "Counter {$counter} Operator",
                assignedCounter: $counter,
                passwordLength: 6,
                numericOnly: true
            );
        }

        CLI::newLine();
        CLI::write('=== Generated credentials (shown once — store these securely) ===', 'yellow');
        foreach (array_filter($created) as $row) {
            CLI::write(sprintf('  %-12s  %s', $row['username'], $row['password']));
        }
        CLI::write('===================================================================', 'yellow');
        CLI::newLine();
    }

    private function createUserIfMissing(
        string $username,
        string $role,
        string $name,
        ?int $assignedCounter,
        int $passwordLength,
        bool $numericOnly = false
    ): ?array {
        $exists = $this->db->table('users')->where('username', $username)->get()->getRow();
        if ($exists) {
            CLI::write("Skipping '{$username}' — already exists.", 'dark_gray');

            return null;
        }

        $password = $numericOnly
            ? (string) random_int(100000, 999999)
            : bin2hex(random_bytes((int) ceil($passwordLength / 2)));

        $this->db->table('users')->insert([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'name' => $name,
            'assigned_counter' => $assignedCounter,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['username' => $username, 'password' => $password];
    }
}
