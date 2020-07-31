<?php
namespace Riki\SubscriptionEmail\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;

class SubscriptionSendCopyEmailMethod implements ArrayInterface
{
    /**
     * Options getter
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /**
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        return [
            'bcc' => __('Bcc'),
            'copy' => __('Separate Email')
        ];
    }
}