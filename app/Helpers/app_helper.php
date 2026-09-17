<?php

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $words = array_slice(array_values(array_filter(explode(' ', $name ?? ''))), 0, 2);

        return mb_strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), $words)));
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
