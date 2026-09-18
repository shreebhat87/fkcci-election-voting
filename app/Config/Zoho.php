<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Zoho CRM integration — an alternative to the Excel upload for pulling
 * master member data, per FKCCI/World Vision Softek's request. A company's
 * membership record in Zoho is assumed to carry BOTH designated contact
 * persons (our two `members` rows) on one record, since that's how Zoho
 * CRM contacts/deals modules are typically laid out for this kind of
 * membership data.
 *
 * IMPORTANT — this build only *simulates* Zoho: `driver` defaults to
 * 'simulated' and SimulatedZohoMembersClient returns fixed sample data,
 * no network call. LiveZohoMembersClient is written against Zoho CRM's
 * documented REST API v2 shape (OAuth2 refresh-token flow, GET
 * /crm/v2/{module}) but has never been run against a real Zoho org —
 * there were no credentials to test with. To go live:
 *
 *   1. Set zoho.driver = live in .env
 *   2. Fill in zoho.clientId / clientSecret / refreshToken / accountsDomain
 *      / apiDomain (region-specific — .com/.in/.eu/.com.au/.jp) in .env
 *   3. Confirm $module and $fieldMap below against the REAL field API
 *      names in your Zoho org (Setup → Customization → Modules and
 *      Fields) — the names here are placeholders and almost certainly
 *      won't match your org's actual field API names.
 *
 * Nothing outside ZohoClientFactory needs to change — every caller only
 * ever depends on ZohoMembersClientInterface.
 */
class Zoho extends BaseConfig
{
    /** 'simulated' | 'live' */
    public string $driver = 'simulated';

    // ---- OAuth2 (live driver only) ----
    public string $clientId = '';
    public string $clientSecret = '';
    public string $refreshToken = '';

    // Region-specific Zoho endpoints — India: accounts.zoho.in / www.zohoapis.in.
    // US/global: accounts.zoho.com / www.zohoapis.com. See Zoho's multi-DC docs.
    public string $accountsDomain = 'https://accounts.zoho.in';
    public string $apiDomain = 'https://www.zohoapis.in';

    /** The Zoho CRM module (tab) holding membership records. */
    public string $module = 'Members';

    /**
     * Our normalized field => Zoho field API name (not the display label —
     * API names are usually underscored, e.g. "Company_Name"). Placeholder
     * values below; confirm against the real module before going live.
     */
    public array $fieldMap = [
        'membership_id' => 'Membership_ID',
        'company_name' => 'Company_Name',

        'contact1_name' => 'Contact_Person_1',
        'contact1_rfid' => 'RFID_Tag_ID_1',
        'contact1_designation' => 'Designation_1',
        'contact1_email' => 'Email_1',
        'contact1_mobile' => 'Contact_Number_1',

        'contact2_name' => 'Contact_Person_2',
        'contact2_rfid' => 'RFID_Tag_ID_2',
        'contact2_designation' => 'Designation_2',
        'contact2_email' => 'Email_2',
        'contact2_mobile' => 'Contact_Number_2',
    ];
}
