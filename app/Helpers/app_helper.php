<?php

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $words = array_slice(array_values(array_filter(explode(' ', $name ?? ''))), 0, 2);

        return mb_strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), $words)));
    }
}

if (! function_exists('formatIST')) {
    /**
     * Formats a stored `Y-m-d H:i:s` timestamp for display: DD:MM:YYYY,
     * 12-hour clock with AM/PM, e.g. "18:09:2026 07:41:05 PM". Every
     * timestamp the app writes is already IST wall-clock time (PHP's
     * default timezone is set to Asia/Kolkata in Config/Constants.php),
     * so this is pure reformatting — the value is explicitly tagged
     * Asia/Kolkata rather than relying on that global still being in
     * effect wherever this happens to be called from.
     */
    function formatIST(?string $datetime): string
    {
        if (! $datetime) {
            return '—';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new \DateTimeZone('Asia/Kolkata'));

        return $dt ? $dt->format('d:m:Y h:i:s A') : $datetime;
    }
}

if (! function_exists('timeAgo')) {
    function timeAgo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);

        if ($diff < 60) {
            return 'just now';
        }

        $mins = (int) round($diff / 60);
        if ($mins < 60) {
            return "{$mins} min ago";
        }

        $hrs = (int) round($mins / 60);

        return "{$hrs} hr" . ($hrs > 1 ? 's' : '') . ' ago';
    }
}
