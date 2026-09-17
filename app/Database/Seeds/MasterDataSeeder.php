<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Test/demo master data — 10 companies with 2 designated members each,
 * mirroring the HTML prototype's mock dataset so the two can be compared
 * side by side. Skips any company that already exists, so it's safe to
 * re-run without duplicating.
 */
class MasterDataSeeder extends Seeder
{
    private array $companies = [
        'Bangalore Precision Tools Pvt Ltd',
        'Karnataka Silk Exports Ltd',
        'South India Steel Traders',
        'Deccan Electronics Pvt Ltd',
        'Mysuru Agro Industries',
        'Cauvery Textiles Pvt Ltd',
        'Vidyanagar Engineering Works',
        'Nandi Hills Foods Pvt Ltd',
        'Garuda Logistics Pvt Ltd',
        'Sandalwood Chemicals Pvt Ltd',
    ];

    private array $namePairs = [
        ['Ramesh Gowda', 'Suma Ramesh'],
        ['Anitha Rao', 'Vikram Rao'],
        ['Manjunath K', 'Deepa Manjunath'],
        ['Suresh Kumar', 'Lakshmi Suresh'],
        ['Prakash Shetty', 'Nandini Shetty'],
        ['Harish Babu', 'Shwetha Harish'],
        ['Ravindra Patil', 'Meera Patil'],
        ['Girish Nayak', 'Pooja Girish'],
        ['Arvind Shenoy', 'Kavya Arvind'],
        ['Naveen Reddy', 'Divya Naveen'],
    ];

    private array $designations = ['Managing Director', 'Director', 'Partner', 'Proprietor'];

    public function run()
    {
        $inserted = 0;

        foreach ($this->companies as $ci => $companyName) {
            $existing = $this->db->table('companies')->where('name', $companyName)->get()->getRow();
            if ($existing) {
                CLI::write("Skipping '{$companyName}' — already exists.", 'dark_gray');
                continue;
            }

            $this->db->table('companies')->insert(['name' => $companyName, 'created_at' => date('Y-m-d H:i:s')]);
            $companyId = $this->db->insertID();

            foreach ($this->namePairs[$ci] as $ni => $name) {
                $suffix = $ni === 0 ? 'A' : 'B';
                $memberId = sprintf('FKCCI-%03d%s', $ci + 1, $suffix);
                $rfid = sprintf('E200%04d%02d9A7B%04d', $ci + 1, $ni + 1, $ci * 7 + $ni);

                $this->db->table('members')->insert([
                    'company_id' => $companyId,
                    'member_id' => $memberId,
                    'rfid_tag' => $rfid,
                    'name' => $name,
                    'designation' => $this->designations[($ci + $ni) % count($this->designations)],
                    'mobile' => sprintf('98%08d', 10000000 + $ci * 111 + $ni * 37),
                    'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $inserted++;
            }
        }

        CLI::write("Seeded {$inserted} members across " . count($this->companies) . ' companies.', 'green');
    }
}
