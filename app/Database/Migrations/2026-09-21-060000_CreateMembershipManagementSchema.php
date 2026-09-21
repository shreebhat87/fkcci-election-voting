<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Membership management: self-service application intake (matching the
 * paper "Application for Membership" form and fee schedule), a simulated-
 * until-configured online payment, and the two-stage approval workflow the
 * paper form itself encodes (Membership Committee recommends, Managing
 * Committee approves/rejects, Secretary registers & issues the
 * certificate/ID card).
 *
 * Design notes:
 *
 * - `companies` grows the organisation's full profile (address, nature of
 *   business, financials, registration numbers) rather than a new table,
 *   since it's already the company entity the election system uses —
 *   membership becomes this repo's source of truth for that data, feeding
 *   the election roll instead of only Excel/Zoho import.
 * - Workflow state lives in a separate `membership_applications` table,
 *   not on `companies` — an application is a point-in-time process with
 *   its own actors/timestamps (who recommended, who approved, when
 *   registered), while `companies` is the durable organisation record.
 *   `membership_status` on `companies` is a cheap denormalized read of
 *   "is this currently an active member" so the election/admin screens
 *   don't need to join applications for that one flag; it defaults
 *   'active' so every company row created before this migration (Excel/
 *   Zoho import, with no membership workflow) is unaffected.
 * - `members.rfid_tag` and `members.member_id` become nullable: a
 *   representative is captured (name/designation/photo) at *application*
 *   time, but the paper form's own numbering only happens at *approval*
 *   ("Entered in Membership Register... Identity Card No...") — so both
 *   are assigned later, by the admin RFID/register-entry action, not at
 *   intake. `is_election_rep` caps which representatives (max 2, per the
 *   election's existing "2 designated members per company" rule) feed the
 *   voting roll — associations/large-scale members may have up to 3
 *   representatives on their membership, but only 2 can vote.
 * - `companies.membership_no` (e.g. "SSO-1326") is the paper form's "MEM
 *   ID No.": a 2-3 letter code (scale + nature-of-business + category
 *   initials, inferred from the one sample card available — e.g. Small +
 *   Service + Ordinary = "SSO") plus a sequential number. This scheme is
 *   inferred from a single example, not confirmed against FKCCI's real
 *   numbering register — treat the generated codes as a placeholder
 *   convention until confirmed, the same caveat already documented for
 *   the Zoho field-name mapping.
 */
class CreateMembershipManagementSchema extends Migration
{
    public function up()
    {
        $this->forge->addColumn('companies', [
            'address' => ['type' => 'TEXT', 'null' => true, 'after' => 'name'],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'address'],
            'mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'phone'],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'mobile'],
            'website' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'email'],
            'year_established' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'website'],
            'nature_of_business' => [
                'type' => 'ENUM', 'null' => true, 'after' => 'year_established',
                'constraint' => ['manufacture', 'trade', 'service', 'profession', 'association', 'district_chamber', 'other_association'],
            ],
            // Small vs Large/Medium only applies to manufacture/trade/service
            // (turnover threshold Rs.10 crore, per the form's instructions);
            // NULL for profession/association/district_chamber/other_association.
            'business_scale' => ['type' => 'ENUM', 'constraint' => ['small', 'large_medium'], 'null' => true, 'after' => 'nature_of_business'],
            'product_description' => ['type' => 'TEXT', 'null' => true, 'after' => 'business_scale'],
            'annual_turnover' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true, 'after' => 'product_description'],
            'gstin' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'annual_turnover'],
            'msme_registration_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'gstin'],
            'pan' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'msme_registration_no'],
            'company_registration_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'pan'],
            'ie_code' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'company_registration_no'],
            'professional_institute_membership_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'ie_code'],
            'bankers_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'professional_institute_membership_no'],
            'membership_category' => ['type' => 'ENUM', 'constraint' => ['ordinary', 'patron'], 'null' => true, 'after' => 'bankers_name'],
            'patron_tier' => ['type' => 'ENUM', 'constraint' => ['platinum', 'gold'], 'null' => true, 'after' => 'membership_category'],
            'membership_no' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'patron_tier'],
            'membership_status' => [
                'type' => 'ENUM', 'null' => false, 'default' => 'active', 'after' => 'membership_no',
                'constraint' => ['prospect', 'pending_application', 'active', 'inactive', 'rejected'],
            ],
        ]);
        $this->db->query('ALTER TABLE `companies` ADD UNIQUE KEY `uq_companies_membership_no` (`membership_no`)');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'unsigned' => true],
            // Public tracking reference — self-registration has no login, so
            // this is how an applicant checks status/pays afterwards.
            'application_ref' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => [
                'type' => 'ENUM', 'null' => false, 'default' => 'awaiting_payment',
                'constraint' => [
                    'awaiting_payment', 'submitted', 'committee_review', 'committee_recommended',
                    'committee_rejected', 'managing_committee_review', 'approved', 'rejected',
                ],
            ],
            'admission_fee' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'subscription_fee' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'gst_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'total_fee' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'proposed_by' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'proposed_by_id_card_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'seconded_by' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'seconded_by_id_card_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'committee_presented_on' => ['type' => 'DATE', 'null' => true],
            'committee_recommended_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'committee_recommended_on' => ['type' => 'DATETIME', 'null' => true],
            'committee_rejection_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'managing_committee_presented_on' => ['type' => 'DATE', 'null' => true],
            'managing_committee_decided_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'managing_committee_decided_on' => ['type' => 'DATETIME', 'null' => true],
            'managing_committee_rejection_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'registered_on' => ['type' => 'DATE', 'null' => true],
            'certificate_issued_on' => ['type' => 'DATE', 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('application_ref');
        $this->forge->addKey('company_id');
        $this->forge->addForeignKey('company_id', 'companies', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('committee_recommended_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('managing_committee_decided_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('membership_applications');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            // 'simulated' until real gateway credentials are configured —
            // see PaymentGatewayFactory. Kept as free text (not an enum) so
            // adding a real gateway later needs no migration.
            'gateway' => ['type' => 'VARCHAR', 'constraint' => 20],
            'gateway_order_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'gateway_payment_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['created', 'paid', 'failed'], 'default' => 'created'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('application_id');
        $this->forge->addForeignKey('application_id', 'membership_applications', 'id', '', 'CASCADE');
        $this->forge->createTable('membership_payments');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'application_id' => ['type' => 'INT', 'unsigned' => true],
            'doc_type' => [
                'type' => 'ENUM',
                'constraint' => ['company_registration_certificate', 'gst_registration_pan', 'audited_profit_loss', 'moa_aoa_or_deed', 'msme_certificate', 'other'],
            ],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'uploaded_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('application_id');
        $this->forge->addForeignKey('application_id', 'membership_applications', 'id', '', 'CASCADE');
        $this->forge->createTable('membership_documents');

        $this->forge->addColumn('members', [
            'application_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'company_id'],
            'is_election_rep' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false, 'default' => 1, 'after' => 'photo_path'],
        ]);
        $this->db->query('ALTER TABLE `members` ADD KEY `idx_members_application_id` (`application_id`)');
        $this->db->query(
            'ALTER TABLE `members` ADD CONSTRAINT `fk_members_application` FOREIGN KEY (`application_id`) REFERENCES `membership_applications` (`id`) ON DELETE SET NULL'
        );
        // member_id/rfid_tag were NOT NULL — a representative is captured at
        // application time but only numbered/tagged at approval. Unique
        // indexes already in place are untouched (MySQL unique keys allow
        // any number of NULLs).
        $this->db->query('ALTER TABLE `members` MODIFY COLUMN `member_id` VARCHAR(30) NULL');
        $this->db->query('ALTER TABLE `members` MODIFY COLUMN `rfid_tag` VARCHAR(64) NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `members` MODIFY COLUMN `rfid_tag` VARCHAR(64) NOT NULL');
        $this->db->query('ALTER TABLE `members` MODIFY COLUMN `member_id` VARCHAR(30) NOT NULL');
        $this->db->query('ALTER TABLE `members` DROP FOREIGN KEY `fk_members_application`');
        $this->db->query('ALTER TABLE `members` DROP KEY `idx_members_application_id`');
        $this->forge->dropColumn('members', ['application_id', 'is_election_rep']);

        $this->forge->dropTable('membership_documents');
        $this->forge->dropTable('membership_payments');
        $this->forge->dropTable('membership_applications');

        $this->db->query('ALTER TABLE `companies` DROP KEY `uq_companies_membership_no`');
        $this->forge->dropColumn('companies', [
            'address', 'phone', 'mobile', 'email', 'website', 'year_established',
            'nature_of_business', 'business_scale', 'product_description', 'annual_turnover',
            'gstin', 'msme_registration_no', 'pan', 'company_registration_no', 'ie_code',
            'professional_institute_membership_no', 'bankers_name', 'membership_category',
            'patron_tier', 'membership_no', 'membership_status',
        ]);
    }
}
