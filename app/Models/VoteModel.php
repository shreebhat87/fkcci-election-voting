<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;
use Config\Database;

class VoteModel extends Model
{
    protected $table = 'votes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'member_id', 'company_id', 'counter_no', 'serial_no', 'status',
        'void_reason', 'issued_by', 'voided_by', 'issued_at', 'voided_at',
    ];
    protected $useTimestamps = false; // we track issued_at/voided_at explicitly

    /**
     * The current issued (non-void) vote for a company, if any — this is
     * what the counter screen shows in the "already voted" alert.
     */
    public function activeVoteForCompany(int $companyId): ?array
    {
        return $this->select('votes.*, members.name as member_name')
            ->join('members', 'members.id = votes.member_id')
            ->where('votes.company_id', $companyId)
            ->where('votes.status', 'issued')
            ->first();
    }

    public function findBySerial(string $serial): ?array
    {
        return $this->select('votes.*, members.name as member_name, members.member_id as member_code,
                members.designation, members.rfid_tag, members.photo_path, companies.name as company_name')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->where('votes.serial_no', trim($serial))
            ->first();
    }

    /**
     * Issue a vote for a member, atomically. Returns one of:
     *   ['status' => 'issued', 'vote' => [...]]
     *   ['status' => 'already_voted', 'vote' => [...]]  // existing active vote
     *
     * Correctness under 10 concurrent counters does NOT come from the
     * pre-check below (that's just a cheap fast path to avoid an
     * unnecessary insert/rollback most of the time) — it comes from the
     * `uq_votes_active_company` unique index on the generated column in
     * the votes table (see the CreateVotesTableMigration). If two counters
     * race past the pre-check at the same instant, one INSERT wins and the
     * other throws a duplicate-key DatabaseException, which we catch below
     * and turn into the same 'already_voted' result the loser would have
     * gotten if their timing had been a few milliseconds later.
     */
    public function issue(array $member, int $counterNo, int $issuedByUserId): array
    {
        $db = Database::connect();

        $existing = $this->activeVoteForCompany($member['company_id']);
        if ($existing) {
            return ['status' => 'already_voted', 'vote' => $existing];
        }

        $db->transStart();

        // serial_no is NOT NULL + UNIQUE, but the human-readable serial
        // depends on the auto-increment id we don't have until after
        // insert — so insert with a throwaway unique placeholder, then
        // rewrite it to the real serial once we have the id, all inside
        // the same transaction.
        $placeholder = 'PENDING-' . bin2hex(random_bytes(12));

        $data = [
            'member_id' => $member['id'],
            'company_id' => $member['company_id'],
            'counter_no' => $counterNo,
            'serial_no' => $placeholder,
            'status' => 'issued',
            'issued_by' => $issuedByUserId,
            'issued_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->insert($data);
        } catch (DatabaseException $e) {
            $db->transRollback();

            if (! $this->isActiveCompanyConflict($e)) {
                throw $e;
            }

            // Lost the race to another counter between our pre-check and
            // this insert. Look up whichever vote won.
            $existing = $this->activeVoteForCompany($member['company_id']);

            return ['status' => 'already_voted', 'vote' => $existing];
        }

        $id = $this->insertID();
        // Dashes, not slashes — a serial embedded directly in a URL path
        // (slip/{serial}, verify/{serial}) must not contain '/' itself.
        $serial = sprintf('FKCCI-%s-%06d', date('Y'), $id);
        $this->update($id, ['serial_no' => $serial]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to record vote due to a database error.');
        }

        return ['status' => 'issued', 'vote' => $this->find($id)];
    }

    public function voidVote(int $voteId, string $reason, int $voidedByUserId): bool
    {
        return $this->update($voteId, [
            'status' => 'void',
            'void_reason' => $reason,
            'voided_by' => $voidedByUserId,
            'voided_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * True only for a duplicate-key violation on the specific unique index
     * that enforces one-vote-per-company — never for the serial_no unique
     * key or an unrelated database error, both of which should propagate.
     */
    private function isActiveCompanyConflict(DatabaseException $e): bool
    {
        return (int) $e->getCode() === 1062
            && str_contains($e->getMessage(), 'uq_votes_active_company');
    }
}
