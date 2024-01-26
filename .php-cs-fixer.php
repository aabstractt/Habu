<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src');

return (new PhpCsFixer\Config)
	->setRiskyAllowed(true)
	->setRules([
		'align_multiline_comment' => [
			'comment_type' => 'phpdocs_only'
		],
		'array_indentation' => true,
        'array_push' => true,
        'no_multiple_statements_per_line' => true,
        'numeric_literal_separator' => [
            'strategy' => 'use_separator',
            'override_existing' => true
        ],
        'modernize_types_casting' => true,
        'final_class' => true,
        'no_null_property_initialization' => true,
        'self_accessor' => true,
        'comment_to_phpdoc' => [
            'ignored_tags' => ['TODO']
        ],
        'no_superfluous_elseif' => true,
        'no_unneeded_braces' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_property_type' => true,
        'void_return' => true,
        'lowercase_keywords' => true,
		'array_syntax' => [
			'syntax' => 'short'
		],
        'is_null' => true,
        'nullable_type_declaration' => [
            'syntax' => 'question_mark'
        ],
        'assign_null_coalescing_to_coalesce_equal' => true,
        'long_to_shorthand_operator' => true,
        'ternary_to_elvis_operator' => true,
		'binary_operator_spaces' => [
			'default' => 'single_space'
		],
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'blank_line_before_statement' => [
			'statements' => [
				'declare'
			]
		],
		'cast_spaces' => [
			'space' => 'single'
		],
		'concat_space' => [
			'spacing' => 'one'
		],
		'declare_strict_types' => true,
		'elseif' => true,
		'fully_qualified_strict_types' => true,
		'global_namespace_import' => [
			'import_constants' => true,
			'import_functions' => true,
			'import_classes' => null,
		],
		'logical_operators' => true,
		'native_function_invocation' => [
			'scope' => 'namespaced',
			'include' => ['@all'],
		],
		'new_with_braces' => [
			'named_class' => true,
			'anonymous_class' => false,
		],
		'no_closing_tag' => true,
		'no_empty_phpdoc' => true,
		'no_extra_blank_lines' => true,
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'no_whitespace_in_blank_line' => true,
		'no_unused_imports' => true,
		'ordered_imports' => [
			'imports_order' => [
				'class',
				'function',
				'const',
			],
			'sort_algorithm' => 'alpha'
		],
		'phpdoc_line_span' => [
			'property' => 'single',
			'method' => null,
			'const' => null
		],
		'phpdoc_trim' => true,
		'phpdoc_trim_consecutive_blank_line_separation' => true,
		'single_import_per_statement' => true,
		'strict_param' => true,
		'unary_operator_spaces' => true,
	])
	->setFinder($finder)
	->setIndent("\t")
	->setLineEnding("\n");
