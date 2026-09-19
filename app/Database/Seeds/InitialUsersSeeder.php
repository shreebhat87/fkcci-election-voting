<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Seeds one admin account, one operator account per counter (1-10), and
 * one exit-desk operator account per EVM confirmation desk. Passwords/
 * PINs are randomly generated and printed once to the console — nothing
 * predictable ships in the repo. Re-running this seeder is safe: it skips
 * any username that already exists rather than duplicating or silently
 * resetting credentials.
 */
class InitialUsersSeeder extends Seeder
{
    /**
     * Nothing in the source material fixes how many exit desks there
     * are (unlike the 10 issuing counters, which is a hard given) — this
     * is a starting assumption. Adjust and rerun `spark db:seed
     * InitialUsersSeeder` to add more; existing accounts are left alone.
     */
    private const EXIT_DESK_COUNT = 4;

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

        for ($desk = 1; $desk <= self::EXIT_DESK_COUNT; $desk++) {
            $created[] = $this->createUserIfMissing(
                username: "exit{$desk}",
                role: 'exit_operator',
                name: "Exit Desk {$desk} Operator",
                assignedCounter: null,
                passwordLength: 6,
                numericOnly: true,
                assignedExitDesk: $desk
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
        bool $numericOnly = false,
        ?int $assignedExitDesk = null
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
            'assigned_exit_desk' => $assignedExitDesk,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['username' => $username, 'password' => $password];
    }
}
