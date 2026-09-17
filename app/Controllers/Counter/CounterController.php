<?php

namespace App\Controllers\Counter;

use App\Controllers\BaseController;
use App\Models\MemberModel;
use App\Models\VoteModel;
use App\Models\CompanyModel;

class CounterController extends BaseController
{
    public function index()
    {
        $counterNo = (int) $this->session->get('assigned_counter');

        return view('counter/index', [
            'counterNo' => $counterNo,
            'operatorName' => $this->session->get('name'),
        ]);
    }

    /** POST /counter/lookup — resolve a scanned RFID tag, no side effects. */
    public function lookup()
    {
        $rfid = trim((string) $this->request->getPost('rfid'));
        if ($rfid === '') {
            return $this->response->setJSON(['status' => 'unknown']);
        }

        $members = model(MemberModel::class);
        $member = $members->findByRfid($rfid);

        if (! $member) {
            return $this->response->setJSON(['status' => 'unknown', 'rfid' => $rfid]);
        }

        $votes = model(VoteModel::class);
        $existing = $votes->activeVoteForCompany((int) $member['company_id']);

        if ($existing) {
            return $this->response->setJSON([
                'status' => 'already_voted',
                'vote' => $this->voteSummary($existing),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'eligible',
            'member' => $this->memberSummary($member),
        ]);
    }

    /** POST /counter/issue — atomically issue a slip for a scanned RFID tag. */
    public function issue()
    {
        $rfid = trim((string) $this->request->getPost('rfid'));
        $counterNo = (int) $this->session->get('assigned_counter');

        if ($rfid === '' || $counterNo < 1) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid request.']);
        }

        $members = model(MemberModel::class);
        $member = $members->findByRfid($rfid);

        if (! $member) {
            return $this->response->setJSON(['status' => 'unknown', 'rfid' => $rfid]);
        }

        $votes = model(VoteModel::class);
        $result = $votes->issue($member, $counterNo, (int) $this->session->get('user_id'));

        if ($result['status'] === 'already_voted') {
            return $this->response->setJSON([
                'status' => 'already_voted',
                'vote' => $this->voteSummary($result['vote']),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'issued',
            'vote' => $this->voteSummaryWithMember($result['vote'], $member),
            'slip_url' => site_url('slip/' . $result['vote']['serial_no']),
        ]);
    }

    /** GET /counter/stats — live tiles + this counter's recent activity, polled after every issue. */
    public function stats()
    {
        $counterNo = (int) $this->session->get('assigned_counter');

        $companies = model(CompanyModel::class);
        $votes = model(VoteModel::class);

        $totalCompanies = $companies->countAll();
        $votedCompanies = $votes->where('status', 'issued')->countAllResults(false, false);
        $votedCompaniesDistinct = count(
            $votes->select('company_id')->where('status', 'issued')->distinct()->findAll()
        );
        $totalVotes = $votes->where('status', 'issued')->countAllResults();
        $counterVotes = $votes->where('status', 'issued')->where('counter_no', $counterNo)->countAllResults();

        $recent = $votes->select('votes.serial_no, votes.issued_at, votes.status, members.name, companies.name as company_name')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->where('votes.counter_no', $counterNo)
            ->orderBy('votes.issued_at', 'DESC')
            ->findAll(6);

        return $this->response->setJSON([
            'counter_votes' => $counterVotes,
            'total_votes' => $totalVotes,
            'voted_companies' => $votedCompaniesDistinct,
            'total_companies' => $totalCompanies,
            'recent' => array_map(function ($v) {
                return [
                    'serial_no' => $v['serial_no'],
                    'name' => $v['name'],
                    'company_name' => $v['company_name'],
                    'status' => $v['status'],
                    'time_ago' => timeAgo($v['issued_at']),
                    'slip_url' => site_url('slip/' . $v['serial_no']),
                ];
            }, $recent),
        ]);
    }

    private function memberSummary(array $member): array
    {
        return [
            'id' => $member['id'],
            'member_id' => $member['member_id'],
            'rfid_tag' => $member['rfid_tag'],
            'name' => $member['name'],
            'designation' => $member['designation'],
            'mobile' => $member['mobile'],
            'company_name' => $member['company_name'],
            'photo_url' => $member['photo_path'] ? site_url('photos/' . basename($member['photo_path'])) : null,
        ];
    }

    private function voteSummary(array $vote): array
    {
        return [
            'serial_no' => $vote['serial_no'],
            'member_name' => $vote['member_name'] ?? null,
            'counter_no' => $vote['counter_no'],
            'issued_at' => $vote['issued_at'],
        ];
    }

    private function voteSummaryWithMember(array $vote, array $member): array
    {
        return [
            'serial_no' => $vote['serial_no'],
            'member_id' => $member['member_id'],
            'name' => $member['name'],
            'company_name' => $member['company_name'],
            'counter_no' => $vote['counter_no'],
            'issued_at' => $vote['issued_at'],
        ];
    }
}
