<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Zoho\ZohoClientException;
use App\Libraries\Zoho\ZohoClientFactory;
use App\Models\CompanyModel;
use App\Models\ImportBatchModel;
use App\Models\MemberModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class MasterDataController extends BaseController
{
    private const REQUIRED_COLUMNS = ['rfid_tag', 'member_id', 'name', 'company_name'];
    private const COLUMN_ALIASES = [
        'rfid_tag' => ['rfid tag id', 'rfid tag', 'rfid'],
        'member_id' => ['member id'],
        'name' => ['member name', 'name'],
        'company_name' => ['company name', 'company'],
        'designation' => ['designation'],
        'mobile' => ['mobile', 'phone', 'mobile number'],
        'email' => ['email', 'email address'],
    ];

    public function index()
    {
        $members = model(MemberModel::class);
        $companies = model(CompanyModel::class);
        $batches = model(ImportBatchModel::class);

        return view('admin/master_data', [
            'active' => 'master-data',
            'loadedCount' => $members->countAllResults(),
            'companyCount' => $companies->countAllResults(),
            'photoCount' => $members->where('photo_path IS NOT NULL')->countAllResults(),
            'noPhotoMembers' => $members->withoutPhoto(),
            'excelHistory' => $batches->recentBySource('members', 'excel'),
            'zohoHistory' => $batches->recentBySource('members', 'zoho'),
            'photoHistory' => $batches->recent('photos'),
            'zohoDriver' => config('Zoho')->driver,
        ]);
    }

    /** POST /admin/master-data/preview-excel — parse + validate, no DB writes. */
    public function previewExcel()
    {
        $file = $this->request->getFile('excel_file');

        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['error' => 'No valid file was uploaded.']);
        }
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls', 'csv'], true)) {
            return $this->response->setJSON(['error' => 'Please upload a .xlsx, .xls, or .csv file.']);
        }

        $tmpDir = WRITEPATH . 'uploads/tmp/';
        @mkdir($tmpDir, 0755, true);
        $token = bin2hex(random_bytes(16));
        $storedName = $token . '.' . $file->getClientExtension();
        $originalName = $file->getClientName();
        $file->move($tmpDir, $storedName);

        [$rawRows, $columnsFound] = $this->parseExcel($tmpDir . $storedName);

        if ($columnsFound === null) {
            unlink($tmpDir . $storedName);

            return $this->response->setJSON(['error' => 'Could not read the file — is it a valid spreadsheet?']);
        }

        $missingRequired = array_diff(self::REQUIRED_COLUMNS, $columnsFound);
        if ($missingRequired) {
            unlink($tmpDir . $storedName);

            return $this->response->setJSON([
                'error' => 'Missing required column(s): ' . implode(', ', $missingRequired),
            ]);
        }

        [$rows, $errors] = $this->validateRawRows($rawRows);

        return $this->response->setJSON([
            'token' => $token,
            'extension' => $file->getClientExtension(),
            'original_name' => $originalName,
            'total_rows' => count($rows) + count($errors),
            'valid_rows' => count($rows),
            'error_rows' => count($errors),
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /** POST /admin/master-data/commit-excel — re-parse the staged file and write to DB. */
    public function commitExcel()
    {
        $token = $this->request->getPost('token');
        $extension = $this->request->getPost('extension');
        $originalName = $this->request->getPost('original_name') ?: 'unknown.xlsx';

        $path = WRITEPATH . "uploads/tmp/{$token}.{$extension}";
        if (! preg_match('/^[a-f0-9]{32}$/', (string) $token) || ! is_file($path)) {
            return $this->response->setJSON(['error' => 'This import session has expired. Please upload the file again.']);
        }

        [$rawRows] = $this->parseExcel($path);
        [$rows, $errors] = $this->validateRawRows($rawRows);
        unlink($path);

        $result = $this->commitRows($rows, $errors, 'excel', $originalName);

        return $this->response->setJSON($result);
    }

    /**
     * POST /admin/master-data/sync-zoho — call the configured Zoho client
     * (simulated or live, see ZohoClientFactory), normalize each company
     * record's two contact persons into member rows, validate, and stage
     * for commit — same preview-then-commit shape as the Excel path, so
     * a sync can be reviewed before anything touches the members table.
     */
    public function syncZoho()
    {
        try {
            $zohoRecords = ZohoClientFactory::make()->fetchMembers();
        } catch (ZohoClientException $e) {
            return $this->response->setJSON(['error' => 'Zoho CRM sync failed: ' . $e->getMessage()]);
        }

        $tmpDir = WRITEPATH . 'uploads/tmp/';
        @mkdir($tmpDir, 0755, true);
        $token = bin2hex(random_bytes(16));
        file_put_contents($tmpDir . "zoho_{$token}.json", json_encode($zohoRecords));

        $rawRows = $this->normalizeZohoRecords($zohoRecords);
        [$rows, $errors] = $this->validateRawRows($rawRows);

        return $this->response->setJSON([
            'token' => $token,
            'company_count' => count($zohoRecords),
            'total_rows' => count($rows) + count($errors),
            'valid_rows' => count($rows),
            'error_rows' => count($errors),
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /** POST /admin/master-data/commit-zoho — re-read the staged Zoho snapshot and write to DB. */
    public function commitZoho()
    {
        $token = $this->request->getPost('token');
        $path = WRITEPATH . "uploads/tmp/zoho_{$token}.json";

        if (! preg_match('/^[a-f0-9]{32}$/', (string) $token) || ! is_file($path)) {
            return $this->response->setJSON(['error' => 'This sync session has expired. Please sync again.']);
        }

        $zohoRecords = json_decode(file_get_contents($path), true) ?? [];
        $rawRows = $this->normalizeZohoRecords($zohoRecords);
        [$rows, $errors] = $this->validateRawRows($rawRows);
        unlink($path);

        $result = $this->commitRows($rows, $errors, 'zoho', 'Zoho CRM Sync');

        return $this->response->setJSON($result);
    }

    /** POST /admin/master-data/preview-photos — extract a ZIP, match filenames to Member IDs, no DB writes. */
    public function previewPhotos()
    {
        $file = $this->request->getFile('photo_zip');

        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['error' => 'No valid file was uploaded.']);
        }
        if (strtolower($file->getClientExtension()) !== 'zip') {
            return $this->response->setJSON(['error' => 'Please upload a .zip file of photos.']);
        }

        $token = bin2hex(random_bytes(16));
        $extractDir = WRITEPATH . "uploads/tmp/photos_{$token}/";
        @mkdir($extractDir, 0755, true);
        $zipPath = WRITEPATH . "uploads/tmp/{$token}.zip";
        $file->move(WRITEPATH . 'uploads/tmp/', "{$token}.zip");

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            unlink($zipPath);

            return $this->response->setJSON(['error' => 'Could not open the zip file.']);
        }
        $zip->extractTo($extractDir);
        $zip->close();
        unlink($zipPath);

        $members = model(MemberModel::class);
        $allMemberIds = array_column($members->select('member_id')->findAll(), 'member_id');

        $matched = [];
        $unmatched = [];
        foreach ($this->listImageFiles($extractDir) as $filePath) {
            $base = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $unmatched[] = basename($filePath) . ' — unsupported file type';
                continue;
            }
            if (in_array($base, $allMemberIds, true)) {
                $matched[] = ['member_id' => $base, 'file' => basename($filePath)];
            } else {
                $unmatched[] = basename($filePath) . ' — no member with this ID';
            }
        }

        return $this->response->setJSON([
            'token' => $token,
            'matched_count' => count($matched),
            'matched' => array_slice($matched, 0, 30),
            'unmatched' => array_slice($unmatched, 0, 20),
            'unmatched_count' => count($unmatched),
        ]);
    }

    /** POST /admin/master-data/commit-photos — copy matched photos into place and update members. */
    public function commitPhotos()
    {
        $token = $this->request->getPost('token');
        $extractDir = WRITEPATH . "uploads/tmp/photos_{$token}/";

        if (! preg_match('/^[a-f0-9]{32}$/', (string) $token) || ! is_dir($extractDir)) {
            return $this->response->setJSON(['error' => 'This import session has expired. Please upload the zip again.']);
        }

        $members = model(MemberModel::class);
        $allMembers = array_column($members->select('id, member_id')->findAll(), null, 'member_id');
        $photoDir = WRITEPATH . 'uploads/photos/';
        @mkdir($photoDir, 0755, true);

        $saved = 0;
        $skipped = 0;
        foreach ($this->listImageFiles($extractDir) as $filePath) {
            $base = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true) || ! isset($allMembers[$base])) {
                $skipped++;
                continue;
            }
            if (@getimagesize($filePath) === false) {
                $skipped++;
                continue;
            }

            $destName = "{$base}.{$ext}";
            copy($filePath, $photoDir . $destName);
            $members->update($allMembers[$base]['id'], ['photo_path' => $destName]);
            $saved++;
        }

        $this->deleteDirectory($extractDir);

        model(ImportBatchModel::class)->insert([
            'type' => 'photos',
            'filename' => 'photos.zip',
            'row_count' => $saved + $skipped,
            'valid_count' => $saved,
            'error_count' => $skipped,
            'imported_by' => $this->session->get('user_id'),
            'imported_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['saved' => $saved, 'skipped' => $skipped]);
    }

    /**
     * Parse an Excel/CSV file into raw (unvalidated) rows. Returns
     * [rawRows, columnsFoundOrNull] — validation is a separate step
     * (validateRawRows) shared with the Zoho path.
     */
    private function parseExcel(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return [[], null];
        }

        $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        if (! $sheetRows) {
            return [[], []];
        }

        $header = array_map(
            static fn ($h) => strtolower(trim((string) $h)),
            array_shift($sheetRows)
        );

        $columnIndex = [];
        foreach (self::COLUMN_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $header, true);
                if ($pos !== false) {
                    $columnIndex[$field] = $pos;
                    break;
                }
            }
        }
        $columnsFound = array_keys($columnIndex);

        $rawRows = [];
        foreach ($sheetRows as $i => $raw) {
            $rowNum = $i + 2; // +1 for 0-index, +1 for header row
            if (! array_filter($raw, static fn ($v) => trim((string) $v) !== '')) {
                continue; // skip fully blank rows
            }

            $get = static fn ($field) => isset($columnIndex[$field]) ? trim((string) ($raw[$columnIndex[$field]] ?? '')) : '';

            $rawRows[] = [
                'row_label' => "Row {$rowNum}",
                'rfid_tag' => $get('rfid_tag'),
                'member_id' => $get('member_id'),
                'name' => $get('name'),
                'company_name' => $get('company_name'),
                'designation' => $get('designation'),
                'mobile' => $get('mobile'),
                'email' => $get('email'),
            ];
        }

        return [$rawRows, $columnsFound];
    }

    /**
     * Turns Zoho's one-record-per-company shape (two contact persons) into
     * the same flat per-member row shape parseExcel() produces, so both
     * sources share one validator and one commit path. Member ID is
     * derived as {membership_id}-A / -B since our schema needs a unique
     * ID per contact, not per company — confirm this convention still
     * makes sense once real Zoho data is available.
     */
    private function normalizeZohoRecords(array $zohoRecords): array
    {
        $rawRows = [];

        foreach ($zohoRecords as $record) {
            $membershipId = $record['membership_id'] ?? '';
            $companyName = $record['company_name'] ?? '';

            foreach ([1, 2] as $n) {
                $rawRows[] = [
                    'row_label' => "Zoho {$membershipId} (contact {$n})",
                    'rfid_tag' => trim((string) ($record["contact{$n}_rfid"] ?? '')),
                    'member_id' => $membershipId !== '' ? "{$membershipId}-" . ($n === 1 ? 'A' : 'B') : '',
                    'name' => trim((string) ($record["contact{$n}_name"] ?? '')),
                    'company_name' => $companyName,
                    'designation' => trim((string) ($record["contact{$n}_designation"] ?? '')),
                    'mobile' => trim((string) ($record["contact{$n}_mobile"] ?? '')),
                    'email' => trim((string) ($record["contact{$n}_email"] ?? '')),
                ];
            }
        }

        return $rawRows;
    }

    /**
     * Shared validation for both import sources: required fields present,
     * and RFID/Member ID not duplicated *within this same batch*. Conflicts
     * against data already in the DB are checked separately at commit time
     * (commitRows), since those can legitimately be updates, not errors.
     *
     * @param list<array{row_label:string, rfid_tag:string, member_id:string, name:string, company_name:string, designation:string, mobile:string, email:string}> $rawRows
     * @return array{0: list<array>, 1: list<string>} [validRows, errorMessages]
     */
    private function validateRawRows(array $rawRows): array
    {
        $rows = [];
        $errors = [];
        $seenRfid = [];
        $seenMemberId = [];

        foreach ($rawRows as $data) {
            $label = $data['row_label'];

            $missing = array_filter(
                ['rfid_tag', 'member_id', 'name', 'company_name'],
                static fn ($f) => $data[$f] === ''
            );
            if ($missing) {
                $errors[] = "{$label}: missing " . implode(', ', $missing) . '.';
                continue;
            }
            if (isset($seenRfid[$data['rfid_tag']])) {
                $errors[] = "{$label}: duplicate RFID tag {$data['rfid_tag']} (also {$seenRfid[$data['rfid_tag']]} in this batch).";
                continue;
            }
            if (isset($seenMemberId[$data['member_id']])) {
                $errors[] = "{$label}: duplicate Member ID {$data['member_id']} (also {$seenMemberId[$data['member_id']]} in this batch).";
                continue;
            }
            if ($data['email'] !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "{$label}: invalid email '{$data['email']}'.";
                continue;
            }

            $seenRfid[$data['rfid_tag']] = $label;
            $seenMemberId[$data['member_id']] = $label;
            $rows[] = $data;
        }

        return [$rows, $errors];
    }

    /**
     * Upserts validated rows into companies/members and logs an
     * import_batches row. Shared by the Excel and Zoho commit endpoints —
     * only $source and $filename differ between them.
     *
     * @param list<array> $rows validated rows from validateRawRows()
     * @param list<string> $preExistingErrors validation errors to carry into the batch log
     */
    private function commitRows(array $rows, array $preExistingErrors, string $source, string $filename): array
    {
        $companies = model(CompanyModel::class);
        $members = model(MemberModel::class);
        $imported = 0;
        $commitErrors = $preExistingErrors;

        foreach ($rows as $row) {
            $company = $companies->findOrCreateByName($row['company_name']);

            $existingByRfid = $members->where('rfid_tag', $row['rfid_tag'])->first();
            if ($existingByRfid && $existingByRfid['member_id'] !== $row['member_id']) {
                $commitErrors[] = "{$row['row_label']}: RFID {$row['rfid_tag']} already belongs to member {$existingByRfid['member_id']}.";
                continue;
            }

            $existingByMemberId = $members->where('member_id', $row['member_id'])->first();
            $data = [
                'company_id' => $company['id'],
                'member_id' => $row['member_id'],
                'rfid_tag' => $row['rfid_tag'],
                'name' => $row['name'],
                'designation' => $row['designation'] ?: null,
                'mobile' => $row['mobile'] ?: null,
                'email' => $row['email'] ?: null,
            ];

            if ($existingByMemberId) {
                $members->update($existingByMemberId['id'], $data);
            } else {
                $members->insert($data);
            }
            $imported++;
        }

        model(ImportBatchModel::class)->insert([
            'type' => 'members',
            'source' => $source,
            'filename' => $filename,
            'row_count' => $imported + count($commitErrors),
            'valid_count' => $imported,
            'error_count' => count($commitErrors),
            'details' => json_encode(array_slice($commitErrors, 0, 50)),
            'imported_by' => $this->session->get('user_id'),
            'imported_at' => date('Y-m-d H:i:s'),
        ]);

        return ['imported' => $imported, 'errors' => count($commitErrors)];
    }

    /** @return list<string> absolute paths of image files found anywhere in the extracted zip */
    private function listImageFiles(string $dir): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->isFile() && ! str_starts_with($f->getFilename(), '.')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
