<?php

namespace WP_Ultimo\Dependencies;

// Don't redefine the functions if included multiple times.
if (!\function_exists('WP_Ultimo\\Dependencies\\GuzzleHttp\\describe_type')) {
    require __DIR__ . '/functions.php';
}
