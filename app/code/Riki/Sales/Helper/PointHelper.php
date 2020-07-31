<?php

namespace Riki\Sales\Helper;


class PointHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Format total value for point
     *
     * @param   \Magento\Framework\DataObject $total
     *
     * @return string
     */
    public function formatUsePoint($total)
    {
        $totalPoint = 0;
        if ($total->getValue() !=null && $total->getValue() >0 ){
            $totalPoint = $total->getValue();
        }
        $numberFormat = \Zend_Locale_Format::toNumber($totalPoint,[ 'number_format' => '#0']);
        return  $numberFormat. __('point');
    }

    /**
     * @param $point
     * @return string
     */
    public function formatUsePointInvoice($point)
    {
        return '(-)'. $point;
    }
}