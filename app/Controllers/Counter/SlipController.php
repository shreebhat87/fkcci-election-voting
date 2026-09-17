<?php

namespace App\Controllers\Counter;

use App\Controllers\BaseController;
use App\Libraries\QrCodeService;
use App\Models\VoteModel;

class SlipController extends BaseController
{
    /**
     * GET /slip/{serial} — the reprintable slip view. A member's RFID can
     * only be scanned once (the vote is now recorded), so this is the only
     * way to view or reprint a slip afterwards — e.g. if the first print
     * failed, went to the wrong printer, or the operator just wants to
     * confirm what was issued.
     */
    public function show(string $serial)
    {
        $vote = model(VoteModel::class)->findBySerial($serial);

        if (! $vote) {
            return view('slip/not_found', ['serial' => $serial]);
        }

        return view('slip/show', ['vote' => $vote]);
    }

    /** GET /slip/{serial}/qr — the slip's QR image, encoding the public verify URL. */
    public function qr(string $serial)
    {
        $vote = model(VoteModel::class)->findBySerial($serial);

        if (! $vote) {
            return $this->response->setStatusCode(404);
        }

        $png = (new QrCodeService())->png(site_url('verify/' . $vote['serial_no']), 300);

        return $this->response
            ->setContentType('image/png')
            ->setBody($png);
    }
}
