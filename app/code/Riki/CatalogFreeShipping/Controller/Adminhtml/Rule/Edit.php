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
 * Class Edit
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Edit extends \Riki\CatalogFreeShipping\Controller\Adminhtml\Rule
{


    /**
     * Edit rule
     *
     * @return void
     */
    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = $this->_initRule();

        $this->_view->loadLayout();
        $this->_setActiveMenu('Riki_CatalogFreeShipping::catalog_free_shipping');

        if (!$model->getId() && $id) {
            $this->messageManager->addError(__('This rule no longer exists.'));
            $this->_redirect('*/*/');
            return;
        }

        if ($id) {
            $breadcrumbTitle = __('Edit Catalog Free Shipping Rule  #%1', $id);
            $breadcrumbLabel = $breadcrumbTitle;
        } else {
            $breadcrumbTitle = __('New Catalog Free Shipping Rule');
            $breadcrumbLabel = __('Create Catalog Free Shipping Rule');
        }

        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Catalog Free Shipping Rule'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Catalog Free Shipping Rule  #%1', $id) : __('New Catalog Free Shipping Rule')
        );

        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

        $values = $this->_getSession()->getData('riki_catalogfs_rule_form_data', true);
        if ($values) {
            $model->addData($values);
        }

        $this->_view->renderLayout();

    }//end execute()


    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CatalogFreeShipping::rule_save');

    }//end _isAllowed()


}//end class
