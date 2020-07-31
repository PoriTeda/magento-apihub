<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Fraud\Ui\Component\Listing\Column\Fraud;

/**
 * Class Options
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [ 'label' =>__('Approve'), 'value' => 'accept'],
            [ 'label' =>__('Review'), 'value' => 'review'],
            [ 'label' =>__('Reject'), 'value' => 'reject']
        ];
    }
}
