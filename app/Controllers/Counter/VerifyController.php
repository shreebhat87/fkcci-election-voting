<?php

namespace App\Controllers\Counter;

use App\Controllers\BaseController;
use App\Models\VoteModel;

/**
 * Public, no-auth QR verification page — what hall-entry staff see when
 * they scan a printed slip's QR code on their own phone.
 */
class VerifyController extends BaseController
{
    public function show(string $serial)
    {
        $vote = model(VoteModel::class)->findBySerial($serial);

        return view('verify/show', [
            'vote' => $vote,
            'photoUrl' => $vote && $vote['photo_path']
                ? site_url('photos/' . basename($vote['photo_path']))
                : null,
        ]);
    }
}
