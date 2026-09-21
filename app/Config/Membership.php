<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Names printed on the ID card's signature lines — taken from the sample
 * card provided ("M Lokaraj, Secretary General I/c" / "M G Balakrishna,
 * President"). These are real office-bearer names at a point in time, not
 * placeholders, but office bearers change — keep this in sync, there's no
 * admin UI for it since it changes rarely and shouldn't be editable by
 * just anyone with admin access.
 */
class Membership extends BaseConfig
{
    public string $secretaryGeneralName = 'M Lokaraj';
    public string $secretaryGeneralTitle = 'Secretary General I/c';
    public string $presidentName = 'M G Balakrishna';
    public string $presidentTitle = 'President';
}
