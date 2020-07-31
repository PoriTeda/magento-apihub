<?php
namespace Riki\Sales\Plugin\Sales\Ui\Component\Listing\Column\Status;

class Options
{
    /**
     * Translate order status label
     *
     * @param \Magento\Sales\Ui\Component\Listing\Column\Status\Options $subject
     * @param $result
     * @return mixed
     */
    public function afterToOptionArray(
        \Magento\Sales\Ui\Component\Listing\Column\Status\Options $subject,
        $result
    ){
        return array_map(function($value){
            $value['label'] = __($value['label']);
            return $value;
        }, $result);
    }
}
