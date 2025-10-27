<?php

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                      => true,
        'array_syntax'                => ['syntax' => 'short'],
        'binary_operator_spaces'      => ['default' => 'align_single_space'],
        'no_unused_imports'           => true,
        'single_quote'                => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'ordered_imports'             => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'declare_strict_types'        => false,
        // add new rule here
        // Format docblock
        'phpdoc_align'                                  => true,
        'phpdoc_indent'                                 => true,
        'phpdoc_trim'                                   => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,

        // Optimize content
        'phpdoc_no_empty_return'       => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_no_package'            => true,
        'phpdoc_no_alias_tag'          => true,
        'phpdoc_scalar'                => true,
        'phpdoc_types'                 => true,
        'phpdoc_types_order'           => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'phpdoc_separation'            => true,

        // Description
        'phpdoc_summary'                => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_var_without_name'       => true,

        // Tags
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order'                        => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_return_self_reference'        => true,
        'phpdoc_inline_tag_normalizer'        => true,
        'phpdoc_tag_casing'                   => true,
        'phpdoc_tag_type'                     => true,
    ]);
