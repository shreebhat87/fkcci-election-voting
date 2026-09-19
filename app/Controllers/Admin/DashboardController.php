<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\MemberModel;
use App\Models\VoteModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $companies = model(CompanyModel::class);
        $members = model(MemberModel::class);
        $votes = model(VoteModel::class);

        $totalMembers = $members->countAllResults();
        $totalCompanies = $companies->countAllResults();

        $votedCompanyIds = array_column(
            $votes->select('company_id')->where('status', 'issued')->distinct()->findAll(),
            'company_id'
        );
        $votedCompanies = count($votedCompanyIds);
        $votesCast = $votes->where('status', 'issued')->countAllResults();

        // "Issued" (a slip was printed) is not the same as "cast" (the
        // member actually surrendered it at the EVM exit desk) — see
        // AddEvmVoteConfirmationTracking. This is the second, harder
        // number: how many issued slips actually turned into a confirmed
        // ballot.
        $evmConfirmed = $votes->where('status', 'issued')->where('voted_at IS NOT NULL')->countAllResults();
        $evmTurnoutPct = $votesCast ? round(($evmConfirmed / $votesCast) * 100) : 0;

        $perCounter = array_fill(1, 10, 0);
        $rows = $votes->select('counter_no, COUNT(*) as cnt')
            ->where('status', 'issued')
            ->groupBy('counter_no')
            ->findAll();
        foreach ($rows as $row) {
            $perCounter[(int) $row['counter_no']] = (int) $row['cnt'];
        }

        $allCompanies = $companies->orderBy('name', 'ASC')->findAll();
        $pendingCompanies = array_values(array_filter(
            $allCompanies,
            static fn ($c) => ! in_array($c['id'], $votedCompanyIds, true)
        ));

        $recent = $votes->select('votes.serial_no, votes.issued_at, votes.status, votes.counter_no, members.name, companies.name as company_name')
            ->join('members', 'members.id = votes.member_id')
            ->join('companies', 'companies.id = votes.company_id')
            ->orderBy('votes.issued_at', 'DESC')
            ->findAll(8);

        return view('admin/dashboard', [
            'active' => 'dashboard',
            'totalMembers' => $totalMembers,
            'totalCompanies' => $totalCompanies,
            'votesCast' => $votesCast,
            'evmConfirmed' => $evmConfirmed,
            'evmTurnoutPct' => $evmTurnoutPct,
            'votedCompanies' => $votedCompanies,
            'pendingCompaniesCount' => $totalCompanies - $votedCompanies,
            'turnoutPct' => $totalCompanies ? round(($votedCompanies / $totalCompanies) * 100) : 0,
            'perCounter' => $perCounter,
            'pendingCompanies' => $pendingCompanies,
            'recent' => $recent,
        ]);
    }
}
