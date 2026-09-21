<?php

namespace App\Libraries\Membership;

/**
 * The fee table printed on page 4 of the paper application form ("FEE
 * STRUCTURE - MEMBERSHIP (Inclusive of GST @ 18%)"). Every figure below is
 * copied verbatim from that table, not recomputed — safest against a
 * transcription error, since these are real money amounts.
 *
 * Two rows in the printed table aren't a direct nature-of-business match
 * for this form's checkboxes: "District Chambers of Commerce" and
 * "Association" happen to carry *identical* fees, so `district_chamber`
 * and `other_association` (a form checkbox sub-option with no fee row of
 * its own) both fall back to the `association` figures below — a safe
 * assumption only because those two rows agree, not a guess about intent.
 *
 * Platinum (Rs. 5,00,000) and Gold (Rs. 3,00,000) are the two flat,
 * one-time "premium patron" tiers called out separately on the form,
 * outside the category/scale grid — treated here as flat totals with no
 * separate admission fee or additional GST line, since the form presents
 * them as bare round numbers ("Rupees Five/Three Lakhs Only"), unlike
 * every other row which explicitly shows a GST-inclusive breakdown.
 */
class FeeScheduleService
{
    /** Flat, GST-inclusive admission fee for every Ordinary application — it's the one figure that never varies by category. */
    private const ORDINARY_ADMISSION_FEE = 1180.00;

    /**
     * [subscription_fee (GST-inclusive), total_fee (== admission + subscription)]
     * for Ordinary membership, by nature-of-business (+ scale where it applies).
     */
    private const ORDINARY_SUBSCRIPTION = [
        'manufacture:small' => [3540.00, 4720.00],
        'manufacture:large_medium' => [8850.00, 10030.00],
        'trade:small' => [3540.00, 4720.00],
        'trade:large_medium' => [8850.00, 10030.00],
        'service:small' => [3540.00, 4720.00],
        'service:large_medium' => [8850.00, 10030.00],
        'profession' => [3540.00, 4720.00],
        'district_chamber' => [7080.00, 8260.00],
        'association' => [7080.00, 8260.00],
    ];

    /** [patron_fee (pre-GST base, as printed), total_fee (GST-inclusive)] by nature-of-business (+ scale where it applies). */
    private const PATRON_FEE = [
        'manufacture:small' => [45000.00, 53100.00],
        'manufacture:large_medium' => [75000.00, 88500.00],
        'trade:small' => [45000.00, 53100.00],
        'trade:large_medium' => [75000.00, 88500.00],
        'service:small' => [45000.00, 53100.00],
        'service:large_medium' => [75000.00, 88500.00],
        'profession' => [45000.00, 53100.00],
        'district_chamber' => [115000.00, 135700.00],
        'association' => [115000.00, 135700.00],
    ];

    private const PATRON_TIER_FLAT = [
        'platinum' => 500000.00,
        'gold' => 300000.00,
    ];

    /**
     * @return array{admission_fee: float, subscription_fee: float, gst_amount: float, total_fee: float}
     */
    public function calculate(
        string $natureOfBusiness,
        ?string $businessScale,
        string $membershipCategory,
        ?string $patronTier = null
    ): array {
        if ($membershipCategory === 'patron' && $patronTier) {
            $flat = self::PATRON_TIER_FLAT[$patronTier] ?? null;
            if ($flat === null) {
                throw new \InvalidArgumentException("Unknown patron tier: {$patronTier}");
            }

            return ['admission_fee' => 0.0, 'subscription_fee' => 0.0, 'gst_amount' => 0.0, 'total_fee' => $flat];
        }

        $key = $this->scheduleKey($natureOfBusiness, $businessScale);

        if ($membershipCategory === 'patron') {
            [$base, $inclusive] = self::PATRON_FEE[$key] ?? throw new \InvalidArgumentException("No patron fee for {$key}");

            return [
                'admission_fee' => 0.0,
                'subscription_fee' => $base,
                'gst_amount' => round($inclusive - $base, 2),
                'total_fee' => $inclusive,
            ];
        }

        [$subscription, $inclusive] = self::ORDINARY_SUBSCRIPTION[$key] ?? throw new \InvalidArgumentException("No ordinary fee for {$key}");
        $admission = self::ORDINARY_ADMISSION_FEE;

        return [
            'admission_fee' => $admission,
            'subscription_fee' => $subscription,
            // Every line in the printed table is already GST-inclusive on
            // its own, so the combined total is a plain sum — this is 0 by
            // construction, kept as a field for symmetry with the patron
            // case above and so the UI always has a "GST" line to show.
            'gst_amount' => round($inclusive - $admission - $subscription, 2),
            'total_fee' => $inclusive,
        ];
    }

    /**
     * The membership_no prefix (e.g. "SSO"): scale-initial + nature-initial
     * + category-initial. Inferred from the one sample ID card available
     * ("Service - Small" + "ORDINARY" => "SSO") — see the schema
     * migration's docblock for the same caveat other inferred conventions
     * in this codebase carry (confirm against FKCCI's real numbering
     * register before relying on it for anything but a demo).
     */
    public function prefixFor(string $natureOfBusiness, ?string $businessScale, string $membershipCategory): string
    {
        $scaleCode = match ($businessScale) {
            'small' => 'S',
            'large_medium' => 'L',
            default => '',
        };
        $natureCode = match ($natureOfBusiness) {
            'manufacture' => 'M',
            'trade' => 'T',
            'service' => 'S',
            'profession' => 'P',
            'association', 'other_association' => 'A',
            'district_chamber' => 'D',
            default => '',
        };
        $categoryCode = $membershipCategory === 'patron' ? 'P' : 'O';

        return $scaleCode . $natureCode . $categoryCode;
    }

    private function scheduleKey(string $natureOfBusiness, ?string $businessScale): string
    {
        $scaled = ['manufacture', 'trade', 'service'];

        if (in_array($natureOfBusiness, $scaled, true)) {
            if (! in_array($businessScale, ['small', 'large_medium'], true)) {
                throw new \InvalidArgumentException("{$natureOfBusiness} requires a business scale.");
            }

            return "{$natureOfBusiness}:{$businessScale}";
        }

        if ($natureOfBusiness === 'other_association') {
            return 'association';
        }

        return $natureOfBusiness;
    }
}
