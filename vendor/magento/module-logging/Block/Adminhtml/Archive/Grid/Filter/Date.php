<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Block\Adminhtml\Archive\Grid\Filter;

/**
 * Custom date column filter for logging archive grid
 */
class Date extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Date
{
    /**
     * Convert date from localized to internal format
     *
     * @param string $date
     * @return string
     */
    protected function _convertDate($date)
    {
        $filterInput = new \Zend_Filter_LocalizedToNormalized(
            [
                'date_format' => $this->_localeDate->getDateFormat(
                    \IntlDateFormatter::SHORT
                ),
            ]
        );
        $filterInternal = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT]
        );
        $date = $filterInput->filter($date);
        $date = $filterInternal->filter($date);

        return $date;
    }
}
