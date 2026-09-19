<?php

namespace App\Controllers\Exit;

use App\Controllers\BaseController;
use App\Models\VoteModel;

class ExitScanController extends BaseController
{
    public function index()
    {
        return view('exit/index', [
            'deskNo' => (int) ($this->session->get('assigned_exit_desk') ?? 0),
            'operatorName' => $this->session->get('name'),
            'isAdmin' => $this->session->get('role') === 'admin',
        ]);
    }

    /**
     * POST /exit/scan — confirm a member cast their EVM ballot, from the
     * QR the exit-desk camera decoded off their surrendered slip.
     */
    public function scan()
    {
        $payload = trim((string) $this->request->getPost('payload'));
        $deskNo = (int) ($this->session->get('assigned_exit_desk') ?? 0);

        if ($payload === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request.']);
        }

        $votes = model(VoteModel::class);
        $result = $votes->markVoted($payload, (int) $this->session->get('user_id'), $deskNo);

        if ($result['status'] === 'not_found') {
            return $this->response->setJSON(['status' => 'not_found']);
        }

        if ($result['status'] === 'void') {
            return $this->response->setJSON([
                'status' => 'void',
                'vote' => $this->voteSummary($result['vote']),
            ]);
        }

        if ($result['status'] === 'already_marked') {
            return $this->response->setJSON([
                'status' => 'already_marked',
                'vote' => $this->voteSummary($result['vote']),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'confirmed',
            'vote' => $this->voteSummary($result['vote']),
        ]);
    }

    /** GET /exit/stats — live tiles + this desk's recent activity. */
    public function stats()
    {
        $deskNo = (int) ($this->session->get('assigned_exit_desk') ?? 0);
        $isAdmin = $this->session->get('role') === 'admin';

        $votes = model(VoteModel::class);

        $totalIssued = $votes->where('status', 'issued')->countAllResults();
        $totalConfirmed = $votes->where('status', 'issued')->where('voted_at IS NOT NULL')->countAllResults();

        $deskQuery = $votes->where('status', 'issued')->where('voted_at IS NOT NULL');
        $deskConfirmed = ($isAdmin && $deskNo < 1)
            ? $totalConfirmed
            : $deskQuery->where('exit_desk_no', $deskNo)->countAllResults();

        $recentQuery = $votes->select('votes.serial_no, votes.voted_at, members.name, companies.name as company_name')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->where('votes.status', 'issued')
            ->where('votes.voted_at IS NOT NULL')
            ->orderBy('votes.voted_at', 'DESC');
        if (! ($isAdmin && $deskNo < 1)) {
            $recentQuery->where('votes.exit_desk_no', $deskNo);
        }
        $recent = $recentQuery->findAll(6);

        return $this->response->setJSON([
            'desk_confirmed' => $deskConfirmed,
            'total_confirmed' => $totalConfirmed,
            'total_issued' => $totalIssued,
            'turnout_pct' => $totalIssued ? round(($totalConfirmed / $totalIssued) * 100) : 0,
            'recent' => array_map(static function ($v) {
                return [
                    'name' => $v['name'],
                    'company_name' => $v['company_name'],
                    'serial_no' => $v['serial_no'],
                    'time_ago' => timeAgo($v['voted_at']),
                ];
            }, $recent),
        ]);
    }

    private function voteSummary(array $vote): array
    {
        return [
            'serial_no' => $vote['serial_no'],
            'member_name' => $vote['member_name'] ?? null,
            'company_name' => $vote['company_name'] ?? null,
            'status' => $vote['status'],
            'void_reason' => $vote['void_reason'] ?? null,
            'voted_at' => formatIST($vote['voted_at'] ?? null),
        ];
    }
}
