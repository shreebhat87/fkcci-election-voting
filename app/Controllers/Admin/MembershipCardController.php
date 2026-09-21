<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\MemberModel;

/** Printable PVC ID card (CR-80, 86mm x 54mm) for an approved, registered representative. */
class MembershipCardController extends BaseController
{
    public function show(string $memberId)
    {
        $rep = model(MemberModel::class)->findByMemberIdWithCompany($memberId);

        if (! $rep || ! $rep['rfid_tag']) {
            return view('admin/membership_card_not_ready', ['memberId' => $memberId, 'rep' => $rep]);
        }

        $company = model(CompanyModel::class)->find($rep['company_id']);

        return view('admin/membership_card', [
            'rep' => $rep,
            'company' => $company,
            'membershipConfig' => config('Membership'),
        ]);
    }
}
