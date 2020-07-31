<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ProductActive\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class Paging implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {

        $options = [];
        $options[] = ['label'=>'5', 'value'=>'5'];
        $options[] = ['label'=>'10', 'value'=>'10'];
        $options[] = ['label'=>'15', 'value'=>'15'];
        $options[] = ['label'=>'20', 'value'=>'20'];
        $options[] = ['label'=>'30', 'value'=>'30'];
        $options[] = ['label'=>'50', 'value'=>'50'];
        $options[] = ['label'=>'100', 'value'=>'100'];

        return $options;
    }
}
