<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Block\Adminhtml\Shipment;

class ImportCsv extends \Magento\Backend\Block\Widget
{
    /**
     * @var string
     */
    protected $_template = 'importcsv.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->setUseContainer(true);
    }
}
