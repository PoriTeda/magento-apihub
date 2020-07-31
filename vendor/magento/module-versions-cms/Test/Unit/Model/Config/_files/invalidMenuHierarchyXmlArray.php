<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    'page_layout_handle_must_be_unique' => [
        '<?xml version="1.0"?><config><menuLayout name="name_one" ><pageLayout handle="node_one_two" />' .
        '<pageLayout handle="node_one_two" /></menuLayout></config>',
        [
            "Element 'pageLayout': Duplicate key-sequence ['node_one_two'] in unique identity-constraint " .
            "'uniquePageLayoutHandle'.\nLine: 1\n"
        ],
    ],
    'menu_layout_name_must_be_unique' => [
        '<?xml version="1.0"?><config><menuLayout name="name_one" /><menuLayout name="name_one" /></config>',
        [
            "Element 'menuLayout': Duplicate key-sequence ['name_one'] in unique identity-constraint " .
            "'uniqueMenuLayoutName'.\nLine: 1\n"
        ],
    ],
    'name_is_required_attribute' => [
        '<?xml version="1.0"?><config><menuLayout /></config>',
        ["Element 'menuLayout': The attribute 'name' is required but missing.\nLine: 1\n"],
    ],
    'handle_is_required_attribute' => [
        '<?xml version="1.0"?><config><menuLayout name="name_one"><pageLayout /></menuLayout></config>',
        ["Element 'pageLayout': The attribute 'handle' is required but missing.\nLine: 1\n"],
    ],
    'handle_with_required_name' => [
        '<?xml version="1.0"?><config><menuLayout name="name_one">' .
            '<pageLayout handle="node(123)" /></menuLayout>' .
        '</config>',
        [
            "Element 'pageLayout', attribute 'handle': [facet 'pattern'] The value 'node(123)'" .
            " is not accepted by the pattern '[1-9A-Za-z_\-]+'.\nLine: 1\n",
            "Element 'pageLayout', attribute 'handle': 'node(123)' is not a valid value of the " .
            "atomic type 'handleName'.\nLine: 1\n",
            "Element 'pageLayout', attribute 'handle': Warning: No precomputed value " .
            "available, the value was either invalid or something strange happend.\nLine: 1\n"
        ],
    ],
    'optional_attributes_with_invalid_names' => [
        '<?xml version="1.0"?><config><menuLayout name="name_one" ' .
        'label="label12" handle="handle(123)" isDefault="12" />' .
        '</config>',
        [
            "Element 'menuLayout', attribute 'handle': [facet 'pattern'] The value 'handle(123)'" .
            " is not accepted by the pattern '[1-9A-Za-z_\-]+'.\nLine: 1\n",
            "Element 'menuLayout', attribute 'handle': 'handle(123)' is not a valid value of the " .
            "atomic type 'handleName'.\nLine: 1\n",
            "Element 'menuLayout', attribute 'isDefault': '12' is not a valid value of the " .
            "atomic type 'xs:boolean'.\nLine: 1\n"
        ],
    ]
];
