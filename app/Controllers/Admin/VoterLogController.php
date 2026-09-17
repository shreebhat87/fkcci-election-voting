<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\VoteModel;

class VoterLogController extends BaseController
{
    public function index()
    {
        $votes = model(VoteModel::class);

        $q = trim((string) $this->request->getGet('q'));
        $counter = $this->request->getGet('counter');
        $status = $this->request->getGet('status');

        $builder = $votes->select('votes.*, members.name as member_name, members.member_id as member_code, companies.name as company_name')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->orderBy('votes.issued_at', 'DESC');

        if ($counter !== null && $counter !== '') {
            $builder->where('votes.counter_no', (int) $counter);
        }
        if ($status !== null && $status !== '') {
            $builder->where('votes.status', $status);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('members.name', $q)
                ->orLike('companies.name', $q)
                ->orLike('members.member_id', $q)
                ->orLike('votes.serial_no', $q)
                ->groupEnd();
        }

        return view('admin/voter_log', [
            'active' => 'voter-log',
            'votes' => $builder->findAll(),
            'q' => $q,
            'counter' => $counter,
            'status' => $status,
        ]);
    }

    /** POST /admin/voter-log/void/{serial} */
    public function void(string $serial)
    {
        $votes = model(VoteModel::class);
        $vote = $votes->findBySerial($serial);

        if (! $vote || $vote['status'] !== 'issued') {
            return redirect()->to('/admin/voter-log')->with('error', 'Slip not found or already void.');
        }

        $reason = trim((string) $this->request->getPost('reason')) ?: 'Not specified';
        $votes->voidVote((int) $vote['id'], $reason, (int) $this->session->get('user_id'));

        return redirect()->to('/admin/voter-log')->with('message', "Slip {$serial} voided.");
    }

    /** GET /admin/voter-log/export — full CSV of every issued/void slip. */
    public function export()
    {
        $votes = model(VoteModel::class)
            ->select('votes.serial_no, members.member_id as member_code, members.name as member_name,
                    companies.name as company_name, members.designation, votes.counter_no,
                    votes.issued_at, votes.status, votes.void_reason')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->orderBy('votes.issued_at', 'DESC')
            ->findAll();

        $csv = "Slip No,Member ID,Name,Company,Designation,Counter,Issued At,Status,Void Reason\n";
        foreach ($votes as $v) {
            $fields = [
                $v['serial_no'], $v['member_code'], $v['member_name'], $v['company_name'],
                $v['designation'], $v['counter_no'], $v['issued_at'], $v['status'], $v['void_reason'],
            ];
            $csv .= implode(',', array_map(static function ($f) {
                return '"' . str_replace('"', '""', (string) $f) . '"';
            }, $fields)) . "\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="fkcci-voter-log.csv"')
            ->setBody($csv);
    }
}
