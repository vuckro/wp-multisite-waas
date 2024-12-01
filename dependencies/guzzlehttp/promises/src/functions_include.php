<?php

namespace WP_Ultimo\Dependencies;

// Don't redefine the functions if included multiple times.
if (!\function_exists('WP_Ultimo\\Dependencies\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
