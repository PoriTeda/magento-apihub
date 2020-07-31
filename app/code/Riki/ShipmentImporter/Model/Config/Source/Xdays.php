<?php
namespace Riki\ShipmentImporter\Model\Config\Source;

class Xdays implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        for ($i=0; $i<=30; $i++) {
            $options[] = ['value'=> $i, 'label' => $i];
        }
        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        for ($i=0; $i<=30; $i++) {
            $options[$i] = $i;
        }
        return $options;
    }
}
