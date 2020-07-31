<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Test\Block\Adminhtml\Promo;

use Magento\Ui\Test\Block\Adminhtml\DataGrid;

/**
 * Class CartPriceRulesGrid
 * Cart Price Rules Grid block on Banner new page
 */
class CartPriceRulesGrid extends DataGrid
{
    /**
     * Initialize block elements
     *
     * @var array
     */
    protected $filters = [
        'name' => [
            'selector' => 'input[name="name"]',
        ],
        'id' => [
            'selector' => 'input[name="rule_id[from]"]',
        ],
    ];
}
