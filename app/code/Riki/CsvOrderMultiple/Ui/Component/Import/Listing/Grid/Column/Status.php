<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\CsvOrderMultiple\Ui\Component\Import\Listing\Grid\Column;


use Riki\CsvOrderMultiple\Api\Data\StatusInterface;
/**
 * Class Options
 */
class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [ 'label' =>__('Waiting'), 'value' => StatusInterface::IMPORT_WAITING],
            [ 'label' =>__('Success'), 'value' => StatusInterface::IMPORT_SUCCESS],
            [ 'label' =>__('Failure'), 'value' => StatusInterface::IMPORT_FAIL],
            [ 'label' =>__('Processing'), 'value' => StatusInterface::IMPORT_PROCESSING]
        ];
    }
}