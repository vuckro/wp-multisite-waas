<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/inc',
			__DIR__ . '/views',
		]
	)
	->withAutoloadPaths(
		[
			__DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
			__DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php',
		]
	)
	->withSkipPath(__DIR__ . '/vendor',)
	->withImportNames(false)
	->withPhpSets()
	->withCodeQualityLevel(15)
	->withCodingStyleLevel(5)
	->withRules(
		[
			\Utils\Rector\Rector\YodaConditionsRector::class,
		]
	);
