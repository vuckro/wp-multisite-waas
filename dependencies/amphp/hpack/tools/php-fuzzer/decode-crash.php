<?php

namespace WP_Ultimo\Dependencies;

require __DIR__ . '/../../vendor/autoload.php';
use WP_Ultimo\Dependencies\Amp\Http\HPack;
(new HPack())->decode(\file_get_contents($argv[1]), 8192);
