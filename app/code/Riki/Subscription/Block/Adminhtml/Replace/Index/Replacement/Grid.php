<?php

namespace Riki\Subscription\Block\Adminhtml\Replace\Index\Replacement;

use Riki\Subscription\Block\Adminhtml\Replace\Index\Grid as IndexGrid;

/**
 * Adminhtml subscription replace create products block
 */
class Grid extends IndexGrid
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('subscription_replace_replacement_grid');
        parent::_construct();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'subscription/*/loadBlock',
            ['block' => 'replacement_grid', '_current' => true, 'collapse' => null]
        );
    }
}
