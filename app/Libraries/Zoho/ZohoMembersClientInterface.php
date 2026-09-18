<?php

namespace App\Libraries\Zoho;

/**
 * One membership record = one company with its two designated contact
 * persons. This is what makes swapping the simulated client for the live
 * one a pure configuration change: MasterDataController never talks to
 * Zoho directly, only to this interface.
 */
interface ZohoMembersClientInterface
{
    /**
     * @return list<array{
     *     membership_id: string,
     *     company_name: string,
     *     contact1_name: string,
     *     contact1_rfid: string,
     *     contact1_designation: ?string,
     *     contact1_email: ?string,
     *     contact1_mobile: ?string,
     *     contact2_name: string,
     *     contact2_rfid: string,
     *     contact2_designation: ?string,
     *     contact2_email: ?string,
     *     contact2_mobile: ?string,
     * }>
     *
     * @throws ZohoClientException on any failure to reach/read Zoho.
     */
    public function fetchMembers(): array;
}
