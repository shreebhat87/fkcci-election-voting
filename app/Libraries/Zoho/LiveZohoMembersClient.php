<?php

namespace App\Libraries\Zoho;

use Config\Zoho as ZohoConfig;

/**
 * Real Zoho CRM v2 REST client — OAuth2 refresh-token flow, then a paginated
 * GET against the configured module. Written against Zoho's documented API
 * shape but UNTESTED against a live org (no credentials were available
 * while building this). Before relying on it:
 *
 *   - Confirm $config->module and $config->fieldMap match your org's real
 *     field API names (Setup → Customization → Modules and Fields — the
 *     API name is shown per field, NOT the display label).
 *   - Confirm accountsDomain/apiDomain match your Zoho data center.
 *   - Confirm the OAuth app's scope includes ZohoCRM.modules.{module}.READ.
 */
class LiveZohoMembersClient implements ZohoMembersClientInterface
{
    private ZohoConfig $config;

    public function __construct(?ZohoConfig $config = null)
    {
        $this->config = $config ?? config('Zoho');
    }

    public function fetchMembers(): array
    {
        $this->assertConfigured();

        $token = $this->getAccessToken();
        $records = $this->fetchAllRecords($token);

        return array_map([$this, 'mapRecord'], $records);
    }

    private function assertConfigured(): void
    {
        if ($this->config->clientId === '' || $this->config->clientSecret === '' || $this->config->refreshToken === '') {
            throw new ZohoClientException(
                'Zoho driver is set to "live" but clientId/clientSecret/refreshToken are not configured. '
                . 'Set them in .env (zoho.clientId, zoho.clientSecret, zoho.refreshToken) — see app/Config/Zoho.php.'
            );
        }
    }

    /** Access tokens last ~1hr; cache to avoid a token request on every sync click. */
    private function getAccessToken(): string
    {
        $cacheKey = 'zoho_access_token';
        $cached = cache($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $client = service('curlrequest');

        try {
            $response = $client->post($this->config->accountsDomain . '/oauth/v2/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->config->clientId,
                    'client_secret' => $this->config->clientSecret,
                    'refresh_token' => $this->config->refreshToken,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new ZohoClientException('Could not reach Zoho accounts server: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode((string) $response->getBody(), true);
        if (! is_array($data) || empty($data['access_token'])) {
            throw new ZohoClientException('Zoho did not return an access token: ' . $response->getBody());
        }

        // Cache for slightly less than Zoho's stated lifetime.
        cache()->save($cacheKey, $data['access_token'], (int) ($data['expires_in'] ?? 3600) - 60);

        return $data['access_token'];
    }

    /** @return list<array<string, mixed>> raw Zoho record data, all pages */
    private function fetchAllRecords(string $accessToken): array
    {
        $client = service('curlrequest');
        $all = [];
        $page = 1;

        do {
            try {
                $response = $client->get(
                    "{$this->config->apiDomain}/crm/v2/{$this->config->module}",
                    [
                        'headers' => ['Authorization' => 'Zoho-oauthtoken ' . $accessToken],
                        'query' => ['page' => $page, 'per_page' => 200],
                    ]
                );
            } catch (\Throwable $e) {
                throw new ZohoClientException('Could not reach Zoho CRM API: ' . $e->getMessage(), 0, $e);
            }

            if ($response->getStatusCode() === 204) {
                break; // no content = no (more) records
            }

            $data = json_decode((string) $response->getBody(), true);
            if (! is_array($data) || ! isset($data['data'])) {
                throw new ZohoClientException('Unexpected response from Zoho CRM API: ' . $response->getBody());
            }

            $all = array_merge($all, $data['data']);
            $moreRecords = (bool) ($data['info']['more_records'] ?? false);
            $page++;
        } while ($moreRecords);

        return $all;
    }

    /** @param array<string, mixed> $record */
    private function mapRecord(array $record): array
    {
        $map = $this->config->fieldMap;
        $get = static fn (string $key) => $record[$map[$key]] ?? null;

        return [
            'membership_id' => (string) $get('membership_id'),
            'company_name' => (string) $get('company_name'),
            'contact1_name' => (string) $get('contact1_name'),
            'contact1_rfid' => (string) $get('contact1_rfid'),
            'contact1_designation' => $get('contact1_designation'),
            'contact1_email' => $get('contact1_email'),
            'contact1_mobile' => $get('contact1_mobile'),
            'contact2_name' => (string) $get('contact2_name'),
            'contact2_rfid' => (string) $get('contact2_rfid'),
            'contact2_designation' => $get('contact2_designation'),
            'contact2_email' => $get('contact2_email'),
            'contact2_mobile' => $get('contact2_mobile'),
        ];
    }
}
