<?php

namespace WP_Ultimo\Dependencies;

if (!\function_exists('WP_Ultimo\\Dependencies\\dd')) {
    function dd(...$args)
    {
        if (\function_exists('WP_Ultimo\\Dependencies\\dump')) {
            dump(...$args);
        } else {
            \var_dump(...$args);
        }
        die;
    }
}
