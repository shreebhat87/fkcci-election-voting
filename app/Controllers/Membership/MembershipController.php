<?php

namespace App\Controllers\Membership;

use App\Controllers\BaseController;
use App\Libraries\Membership\FeeScheduleService;
use App\Libraries\Payment\PaymentGatewayFactory;
use App\Models\CompanyModel;
use App\Models\MemberModel;
use App\Models\MembershipApplicationModel;
use App\Models\MembershipDocumentModel;
use App\Models\MembershipPaymentModel;
use Config\Database;

/**
 * Public, unauthenticated self-registration — the online equivalent of the
 * paper "Application for Membership" form. No applicant account/login:
 * an applicant is identified only by the application_ref shown after
 * submitting, the same pattern the election side uses for slip serials.
 */
class MembershipController extends BaseController
{
    private const SCALED_NATURES = ['manufacture', 'trade', 'service'];
    private const MULTI_REP_NATURES = ['association', 'district_chamber', 'other_association'];

    public function apply()
    {
        return view('membership/apply', [
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function submit()
    {
        $rules = [
            'name' => 'required|max_length[255]',
            'address' => 'required',
            'mobile' => 'required|max_length[20]',
            'email' => 'permit_empty|valid_email',
            'year_established' => 'permit_empty|is_natural|less_than_equal_to[' . date('Y') . ']',
            'nature_of_business' => 'required|in_list[manufacture,trade,service,profession,association,district_chamber,other_association]',
            'business_scale' => 'permit_empty|in_list[small,large_medium]',
            'membership_category' => 'required|in_list[ordinary,patron]',
            'patron_tier' => 'permit_empty|in_list[platinum,gold]',
            'rep1_name' => 'required|max_length[150]',
            'rep2_name' => 'required|max_length[150]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('membership/apply')->withInput()->with('errors', $this->validator->getErrors());
        }

        $nature = $this->request->getPost('nature_of_business');
        $scale = $this->request->getPost('business_scale');
        $category = $this->request->getPost('membership_category');
        $patronTier = $category === 'patron' ? $this->request->getPost('patron_tier') : null;

        if (in_array($nature, self::SCALED_NATURES, true) && ! in_array($scale, ['small', 'large_medium'], true)) {
            return redirect()->to('membership/apply')->withInput()
                ->with('errors', ['business_scale' => 'Select Small or Large/Medium for this nature of business.']);
        }

        $maxReps = $this->maxRepresentatives($nature, $scale);
        $repNames = array_filter([
            1 => trim((string) $this->request->getPost('rep1_name')),
            2 => trim((string) $this->request->getPost('rep2_name')),
            3 => trim((string) $this->request->getPost('rep3_name')),
        ]);
        if (count($repNames) > $maxReps) {
            return redirect()->to('membership/apply')->withInput()
                ->with('errors', ['representatives' => "This category may nominate at most {$maxReps} representatives."]);
        }

        try {
            $fees = (new FeeScheduleService())->calculate($nature, $scale, $category, $patronTier);
        } catch (\InvalidArgumentException $e) {
            return redirect()->to('membership/apply')->withInput()->with('errors', ['fees' => $e->getMessage()]);
        }

        $db = Database::connect();
        $db->transStart();

        try {
            $companies = model(CompanyModel::class);
            $company = $companies->findOrCreateByName((string) $this->request->getPost('name'));
            $companies->update($company['id'], [
                'address' => $this->request->getPost('address'),
                'phone' => $this->request->getPost('phone'),
                'mobile' => $this->request->getPost('mobile'),
                'email' => $this->request->getPost('email'),
                'website' => $this->request->getPost('website'),
                'year_established' => $this->request->getPost('year_established') ?: null,
                'nature_of_business' => $nature,
                'business_scale' => in_array($nature, self::SCALED_NATURES, true) ? $scale : null,
                'product_description' => $this->request->getPost('product_description'),
                'annual_turnover' => $this->request->getPost('annual_turnover') ?: null,
                'gstin' => $this->request->getPost('gstin'),
                'msme_registration_no' => $this->request->getPost('msme_registration_no'),
                'pan' => $this->request->getPost('pan'),
                'company_registration_no' => $this->request->getPost('company_registration_no'),
                'ie_code' => $this->request->getPost('ie_code'),
                'professional_institute_membership_no' => $this->request->getPost('professional_institute_membership_no'),
                'bankers_name' => $this->request->getPost('bankers_name'),
                'membership_category' => $category,
                'patron_tier' => $patronTier,
                'membership_status' => 'pending_application',
            ]);

            $applications = model(MembershipApplicationModel::class);
            $ref = $applications->generateRef();
            $applicationId = $applications->insert([
                'company_id' => $company['id'],
                'application_ref' => $ref,
                'status' => 'awaiting_payment',
                'admission_fee' => $fees['admission_fee'],
                'subscription_fee' => $fees['subscription_fee'],
                'gst_amount' => $fees['gst_amount'],
                'total_fee' => $fees['total_fee'],
                'submitted_at' => date('Y-m-d H:i:s'),
            ], true);

            $members = model(MemberModel::class);
            $repIndex = 0;
            foreach ($repNames as $n => $repName) {
                $repIndex++;
                $photoField = "rep{$n}_photo";
                $photoPath = $this->storeRepPhoto($photoField, $ref, $n);

                $members->insert([
                    'company_id' => $company['id'],
                    'application_id' => $applicationId,
                    'name' => $repName,
                    'designation' => trim((string) $this->request->getPost("rep{$n}_designation")),
                    'mobile' => trim((string) $this->request->getPost("rep{$n}_mobile")),
                    'email' => trim((string) $this->request->getPost("rep{$n}_email")),
                    'photo_path' => $photoPath,
                    // Election voting is capped at 2 designated reps per
                    // company regardless of how many a membership carries.
                    'is_election_rep' => $repIndex <= 2 ? 1 : 0,
                ]);
            }

            $this->storeDocuments((int) $applicationId);

            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Membership application submission failed: ' . $e->getMessage());

            return redirect()->to('membership/apply')->withInput()
                ->with('errors', ['general' => 'Something went wrong saving your application. Please try again.']);
        }

        return redirect()->to('membership/pay/' . $ref);
    }

    public function pay(string $ref)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->withCompanyByRef($ref);

        if (! $application) {
            return view('membership/not_found', ['ref' => $ref]);
        }
        if ($application['status'] !== 'awaiting_payment') {
            return redirect()->to('membership/status/' . $ref);
        }

        $gateway = PaymentGatewayFactory::make();
        $order = $gateway->createOrder((float) $application['total_fee'], $ref);

        $payments = model(MembershipPaymentModel::class);
        $payments->insert([
            'application_id' => $application['id'],
            'amount' => $application['total_fee'],
            'gateway' => config('PaymentGateway')->driver,
            'gateway_order_id' => $order['order_id'],
            'status' => 'created',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return view('membership/pay', [
            'application' => $application,
            'order' => $order,
            'simulated' => config('PaymentGateway')->driver !== 'live',
        ]);
    }

    /** POST /membership/pay/{ref}/confirm — simulated gateway "Pay Now", or a real gateway's redirect/callback landing here. */
    public function confirmPayment(string $ref)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->findByRef($ref);

        if (! $application || $application['status'] !== 'awaiting_payment') {
            return redirect()->to('membership/status/' . $ref);
        }

        $payments = model(MembershipPaymentModel::class);
        $payment = $payments->latestForApplication((int) $application['id']);

        $gateway = PaymentGatewayFactory::make();
        $result = $gateway->verifyPayment($this->request->getPost() ?? []);

        if (! $result['success']) {
            return redirect()->to('membership/pay/' . $ref)->with('error', 'Payment could not be verified. Please try again.');
        }

        $payments->update($payment['id'], [
            'gateway_payment_id' => $result['payment_id'],
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        $applications->update($application['id'], ['status' => 'submitted']);

        return redirect()->to('membership/status/' . $ref);
    }

    /** GET /membership/status — "enter your reference" lookup, for the applicant who doesn't have the direct link saved. */
    public function statusLookup()
    {
        $ref = trim((string) $this->request->getGet('ref'));
        if ($ref !== '') {
            return redirect()->to('membership/status/' . $ref);
        }

        return view('membership/status_lookup');
    }

    public function status(string $ref)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->withCompanyByRef($ref);

        if (! $application) {
            return view('membership/not_found', ['ref' => $ref]);
        }

        $reps = model(MemberModel::class)->forApplication((int) $application['id']);

        return view('membership/status', ['application' => $application, 'reps' => $reps]);
    }

    private function maxRepresentatives(string $nature, ?string $scale): int
    {
        if (in_array($nature, self::MULTI_REP_NATURES, true)) {
            return 3;
        }
        if (in_array($nature, self::SCALED_NATURES, true) && $scale === 'large_medium') {
            return 3;
        }

        return 2;
    }

    private function storeRepPhoto(string $fieldName, string $ref, int $repIndex): ?string
    {
        $file = $this->request->getFile($fieldName);
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $ext = strtolower($file->getClientExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $storedName = strtolower($ref) . '-rep' . $repIndex . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move(WRITEPATH . 'uploads/photos/', $storedName);

        return $storedName;
    }

    private function storeDocuments(int $applicationId): void
    {
        $docTypes = [
            'doc_company_reg' => 'company_registration_certificate',
            'doc_gst_pan' => 'gst_registration_pan',
            'doc_audited_pl' => 'audited_profit_loss',
            'doc_moa_aoa' => 'moa_aoa_or_deed',
            'doc_msme' => 'msme_certificate',
        ];

        $documents = model(MembershipDocumentModel::class);
        $dir = WRITEPATH . 'uploads/membership_docs/' . $applicationId . '/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($docTypes as $field => $docType) {
            $file = $this->request->getFile($field);
            if (! $file || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $ext = strtolower($file->getClientExtension());
            if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $original = $file->getClientName();
            $storedName = $docType . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $file->move($dir, $storedName);

            $documents->insert([
                'application_id' => $applicationId,
                'doc_type' => $docType,
                'file_path' => $applicationId . '/' . $storedName,
                'original_filename' => $original,
                'uploaded_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
