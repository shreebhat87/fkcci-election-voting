<?php

namespace App\Libraries\Zoho;

/**
 * The one place that decides simulated vs live. Every caller depends only
 * on ZohoMembersClientInterface, so going live later is exactly:
 * set zoho.driver=live and the credentials in .env — no code changes.
 */
class ZohoClientFactory
{
    public static function make(): ZohoMembersClientInterface
    {
        $config = config('Zoho');

        return $config->driver === 'live'
            ? new LiveZohoMembersClient($config)
            : new SimulatedZohoMembersClient();
    }
}
