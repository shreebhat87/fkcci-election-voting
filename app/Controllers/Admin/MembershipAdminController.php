<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Membership\FeeScheduleService;
use App\Models\CompanyModel;
use App\Models\MemberModel;
use App\Models\MembershipApplicationModel;
use App\Models\MembershipDocumentModel;
use App\Models\MembershipPaymentModel;
use CodeIgniter\Files\File;
use Config\Database;

class MembershipAdminController extends BaseController
{
    /** GET /admin/membership — queue, filterable by workflow status. */
    public function index()
    {
        $applications = model(MembershipApplicationModel::class);
        $status = $this->request->getGet('status');

        $builder = $applications->select('membership_applications.*, companies.name as company_name, companies.membership_no, companies.membership_category')
            ->join('companies', 'companies.id = membership_applications.company_id')
            ->orderBy('membership_applications.submitted_at', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('membership_applications.status', $status);
        }

        return view('admin/membership_queue', [
            'active' => 'membership',
            'applications' => $builder->findAll(),
            'status' => $status,
        ]);
    }

    /** GET /admin/membership/{id} — full detail + workflow actions. */
    public function show(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->withCompany($id);

        if (! $application) {
            return redirect()->to('admin/membership')->with('error', 'Application not found.');
        }

        return view('admin/membership_detail', [
            'active' => 'membership',
            'application' => $application,
            'reps' => model(MemberModel::class)->forApplication($id),
            'documents' => model(MembershipDocumentModel::class)->forApplication($id),
            'payment' => model(MembershipPaymentModel::class)->latestForApplication($id),
        ]);
    }

    public function presentToCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'submitted') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application is not awaiting committee presentation.');
        }

        $applications->presentToCommittee(
            $id,
            $this->request->getPost('presented_on') ?: date('Y-m-d'),
            trim((string) $this->request->getPost('proposed_by')) ?: null,
            trim((string) $this->request->getPost('proposed_by_id_card_no')) ?: null,
            trim((string) $this->request->getPost('seconded_by')) ?: null,
            trim((string) $this->request->getPost('seconded_by_id_card_no')) ?: null,
        );

        return redirect()->to("admin/membership/{$id}")->with('message', 'Presented to Membership Committee.');
    }

    public function recommendByCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'committee_review') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application is not at the committee review stage.');
        }

        $applications->recommendByCommittee($id, (int) $this->session->get('user_id'));

        return redirect()->to("admin/membership/{$id}")->with('message', 'Recommended by Membership Committee.');
    }

    public function rejectByCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'committee_review') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application is not at the committee review stage.');
        }

        $reason = trim((string) $this->request->getPost('reason')) ?: 'Not specified';
        $applications->rejectByCommittee($id, $reason, (int) $this->session->get('user_id'));
        model(CompanyModel::class)->update($application['company_id'], ['membership_status' => 'rejected']);

        return redirect()->to('admin/membership')->with('message', 'Application rejected at committee stage.');
    }

    public function presentToManagingCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'committee_recommended') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application has not been recommended by the committee yet.');
        }

        $applications->presentToManagingCommittee($id, $this->request->getPost('presented_on') ?: date('Y-m-d'));

        return redirect()->to("admin/membership/{$id}")->with('message', 'Presented to Managing Committee.');
    }

    public function approveByManagingCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'managing_committee_review') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application is not at the managing committee review stage.');
        }

        $applications->approveByManagingCommittee($id, (int) $this->session->get('user_id'));

        return redirect()->to("admin/membership/{$id}")->with('message', 'Approved by Managing Committee. You can now register the member and issue numbers below.');
    }

    public function rejectByManagingCommittee(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->find($id);
        if (! $application || $application['status'] !== 'managing_committee_review') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application is not at the managing committee review stage.');
        }

        $reason = trim((string) $this->request->getPost('reason')) ?: 'Not specified';
        $applications->rejectByManagingCommittee($id, $reason, (int) $this->session->get('user_id'));
        model(CompanyModel::class)->update($application['company_id'], ['membership_status' => 'rejected']);

        return redirect()->to('admin/membership')->with('message', 'Application rejected by Managing Committee.');
    }

    /**
     * "Entered in Membership Register... Identity Card No..." — assigns
     * the company's membership_no and each representative's member_id in
     * one shot. RFID tags themselves are assigned separately per
     * representative (assignRfid below), since tags are tapped one at a
     * time as members physically collect their card, which may not all
     * happen the same day as registration.
     */
    public function register(int $id)
    {
        $applications = model(MembershipApplicationModel::class);
        $application = $applications->withCompany($id);
        if (! $application || $application['status'] !== 'approved') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Application must be approved by the Managing Committee first.');
        }
        if ($application['company']['membership_no']) {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Already registered.');
        }

        $db = Database::connect();
        $db->transStart();

        $companies = model(CompanyModel::class);
        $company = $application['company'];
        $fees = new FeeScheduleService();
        $prefix = $fees->prefixFor($company['nature_of_business'], $company['business_scale'], $company['membership_category']);
        $membershipNo = $companies->nextMembershipNo($prefix);

        $companies->update($company['id'], ['membership_no' => $membershipNo, 'membership_status' => 'active']);

        $members = model(MemberModel::class);
        $suffixes = ['A', 'B', 'C'];
        foreach ($members->forApplication($id) as $i => $rep) {
            $members->update($rep['id'], ['member_id' => $membershipNo . '-' . ($suffixes[$i] ?? ($i + 1))]);
        }

        $today = date('Y-m-d');
        $applications->markRegistered($id, $today, $today);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Registration failed — please retry.');
        }

        return redirect()->to("admin/membership/{$id}")->with('message', "Registered as {$membershipNo}. Assign RFID tags below as members collect their cards.");
    }

    /** GET /admin/membership/document/{docId} — admin-only download of a supporting document (never public, unlike photos). */
    public function downloadDocument(int $docId)
    {
        $doc = model(MembershipDocumentModel::class)->find($docId);
        if (! $doc) {
            return $this->response->setStatusCode(404);
        }

        $path = WRITEPATH . 'uploads/membership_docs/' . $doc['file_path'];
        if (! is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        $file = new File($path);

        return $this->response
            ->setContentType($file->getMimeType())
            ->setHeader('Content-Disposition', 'inline; filename="' . $doc['original_filename'] . '"')
            ->setBody(file_get_contents($path));
    }

    /** POST /admin/membership/{id}/rep/{repId}/rfid — tap-to-assign an RFID tag to one representative. */
    public function assignRfid(int $id, int $repId)
    {
        $rfid = trim((string) $this->request->getPost('rfid_tag'));
        if ($rfid === '') {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Scan or enter an RFID tag.');
        }

        $members = model(MemberModel::class);
        $rep = $members->find($repId);
        if (! $rep || (int) $rep['application_id'] !== $id) {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Representative not found on this application.');
        }
        if (! $rep['member_id']) {
            return redirect()->to("admin/membership/{$id}")->with('error', 'Register the application before assigning RFID tags.');
        }

        $existing = $members->where('rfid_tag', $rfid)->first();
        if ($existing && (int) $existing['id'] !== $repId) {
            return redirect()->to("admin/membership/{$id}")->with('error', "That RFID tag is already assigned to {$existing['name']} ({$existing['member_id']}).");
        }

        $members->update($repId, ['rfid_tag' => $rfid]);

        return redirect()->to("admin/membership/{$id}")->with('message', "RFID tag assigned to {$rep['name']}.");
    }
}
