<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogFreeShipping\Controller\Adminhtml\Rule;

/**
 * Class Index
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends \Riki\CatalogFreeShipping\Controller\Adminhtml\Rule
{

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CatalogFreeShipping::catalog_free_shipping');
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /*
            * @var \Magento\Backend\Model\View\Result\Page $resultPage
        */

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_CatalogFreeShipping::catalog_free_shipping');
        $resultPage->getConfig()->getTitle()->prepend(__('Catalog Free Shipping Rule Management'));
        return $resultPage;

    }//end execute()


}//end class
