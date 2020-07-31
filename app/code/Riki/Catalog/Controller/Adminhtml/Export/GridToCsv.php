<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Catalog\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Model\Export\ConvertToCsv;
use Magento\Framework\App\Response\Http\FileFactory;

/**
 * Class Render
 */
class GridToCsv extends \Magento\Ui\Controller\Adminhtml\Export\GridToCsv
{

    /**
     * @param Context $context
     * @param ConvertToCsv $converter
     * @param FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Model\Export\ConvertToCsv $converter,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->converter = $converter;
        $this->fileFactory = $fileFactory;

        parent::__construct($context,$converter,$fileFactory);
    }


    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface
     *
     * @throws \Exception
     */
    public function execute()
    {
        if('product_listing' == $this->getRequest()->getParam('namespace')){
            try {
                return $this->converter->getProductCsvFile();
            }
            catch(\Exception $e){
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('catalog/product');
            }
        }
        else{
            return $this->fileFactory->create('export.csv', $this->converter->getCsvFile(), 'var');
        }

    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        if('sales_order_grid' == $this->getRequest()->getParam('namespace')) {
            return $this->_authorization->isAllowed('Magento_Sales::actions_view');
        }
        else{
            return true;
        }
    }
}
