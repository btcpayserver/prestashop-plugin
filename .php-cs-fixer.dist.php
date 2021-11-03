<?php

$finder = (new PhpCsFixer\Finder)
	->in([__DIR__ . '/modules/btcpay'])
	->exclude('vendor');

return (new PhpCsFixer\Config())
	->setRules([
		'@Symfony'                     => true,
		'@Symfony:risky'               => true,
		'array_syntax'                 => ['syntax' => 'short'],
		'binary_operator_spaces'       => false,
		'blank_line_after_opening_tag' => false,
		'cast_spaces'                  => ['space' => 'single'],
		'combine_nested_dirname'       => true,
		'concat_space'                 => false,
		'fopen_flags'                  => false,
		'native_function_invocation'   => false,
		'phpdoc_summary'               => false,
		'protected_to_private'         => false,
		'psr_autoloading'              => false,
		'yoda_style'                   => true,
	])
	->setRiskyAllowed(true)
	->setUsingCache(true)
	->setIndent("\t")
	->setFinder($finder);
