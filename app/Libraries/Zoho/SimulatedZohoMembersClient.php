<?php

namespace App\Libraries\Zoho;

/**
 * Stand-in for the real Zoho CRM pull — fixed sample data, no network call.
 * Deliberately uses a different set of companies than MasterDataSeeder's
 * Excel-import demo data, so a sync's results are obviously distinguishable
 * from what's already loaded when testing. Also deliberately includes one
 * incomplete record (missing an RFID tag) so the same validation path the
 * Excel import uses gets exercised here too, not just the happy path.
 */
class SimulatedZohoMembersClient implements ZohoMembersClientInterface
{
    public function fetchMembers(): array
    {
        // A real API call isn't instant — simulate that latency so the
        // admin UI's loading state is tested against something realistic,
        // not a call that resolves suspiciously faster than Excel parsing.
        usleep(600_000);

        return [
            [
                'membership_id' => 'ZM-2001',
                'company_name' => 'Chamundi Precision Castings Pvt Ltd',
                'contact1_name' => 'Basavaraj Hiremath',
                'contact1_rfid' => 'E200ZOHO0101010001',
                'contact1_designation' => 'Managing Director',
                'contact1_email' => 'basavaraj.hiremath@example.com',
                'contact1_mobile' => '9845010001',
                'contact2_name' => 'Vinutha Hiremath',
                'contact2_rfid' => 'E200ZOHO0101010002',
                'contact2_designation' => 'Director',
                'contact2_email' => 'vinutha.hiremath@example.com',
                'contact2_mobile' => '9845010002',
            ],
            [
                'membership_id' => 'ZM-2002',
                'company_name' => 'Kaveri Organic Coffee Exports LLP',
                'contact1_name' => 'Thomas Kurian',
                'contact1_rfid' => 'E200ZOHO0101010003',
                'contact1_designation' => 'Partner',
                'contact1_email' => 'thomas.kurian@example.com',
                'contact1_mobile' => '9845010003',
                'contact2_name' => 'Elizabeth Kurian',
                'contact2_rfid' => 'E200ZOHO0101010004',
                'contact2_designation' => 'Partner',
                'contact2_email' => 'elizabeth.kurian@example.com',
                'contact2_mobile' => '9845010004',
            ],
            [
                'membership_id' => 'ZM-2003',
                'company_name' => 'Nrupathunga Furniture Works',
                'contact1_name' => 'Manjula Devi',
                'contact1_rfid' => 'E200ZOHO0101010005',
                'contact1_designation' => 'Proprietor',
                'contact1_email' => 'manjula.devi@example.com',
                'contact1_mobile' => '9845010005',
                'contact2_name' => 'Ramachandra Setty',
                // Deliberately blank — exercises the "missing RFID" validation
                // path the same way a bad Excel row would.
                'contact2_rfid' => '',
                'contact2_designation' => 'Authorized Signatory',
                'contact2_email' => 'ramachandra.setty@example.com',
                'contact2_mobile' => '9845010006',
            ],
            [
                'membership_id' => 'ZM-2004',
                'company_name' => 'Vidyaranya Digital Print Solutions',
                'contact1_name' => 'Farhan Ahmed',
                'contact1_rfid' => 'E200ZOHO0101010007',
                'contact1_designation' => 'Managing Partner',
                'contact1_email' => 'farhan.ahmed@example.com',
                'contact1_mobile' => '9845010007',
                'contact2_name' => 'Salma Ahmed',
                'contact2_rfid' => 'E200ZOHO0101010008',
                'contact2_designation' => 'Partner',
                'contact2_email' => 'salma.ahmed@example.com',
                'contact2_mobile' => '9845010008',
            ],
            [
                'membership_id' => 'ZM-2005',
                'company_name' => 'Ashoka Rubber & Polymer Industries',
                'contact1_name' => 'Devaraj Urs',
                'contact1_rfid' => 'E200ZOHO0101010009',
                'contact1_designation' => 'Director',
                'contact1_email' => 'devaraj.urs@example.com',
                'contact1_mobile' => '9845010009',
                'contact2_name' => 'Chandrika Urs',
                'contact2_rfid' => 'E200ZOHO0101010010',
                'contact2_designation' => 'Director',
                'contact2_email' => 'chandrika.urs@example.com',
                'contact2_mobile' => '9845010010',
            ],
            [
                'membership_id' => 'ZM-2006',
                'company_name' => 'Brindavan Cold Chain Logistics Pvt Ltd',
                'contact1_name' => 'Joseph Fernandes',
                'contact1_rfid' => 'E200ZOHO0101010011',
                'contact1_designation' => 'Managing Director',
                'contact1_email' => 'joseph.fernandes@example.com',
                'contact1_mobile' => '9845010011',
                'contact2_name' => 'Anita Fernandes',
                'contact2_rfid' => 'E200ZOHO0101010012',
                'contact2_designation' => 'Director',
                'contact2_email' => 'anita.fernandes@example.com',
                'contact2_mobile' => '9845010012',
            ],
        ];
    }
}
