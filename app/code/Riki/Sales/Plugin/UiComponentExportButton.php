<?php
namespace Riki\Sales\Plugin;


class UiComponentExportButton
{

    /**
     * @param \Magento\Ui\Component\ExportButton $subject
     */
    public function beforePrepare(
        $subject
    ) {

        $config = $subject->getData('config');

        if(isset($config['selectProvider']) && strpos($config['selectProvider'], 'sales_order_grid') !== false){
            if (isset($config['options'])) {
                $options = [];
                foreach ($config['options'] as $option) {
                    if($option['value'] != 'xml')
                        $options[] = $option;
                }
                $config['options'] = $options;
                $subject->setData('config', $config);
            }
        }
    }
}
