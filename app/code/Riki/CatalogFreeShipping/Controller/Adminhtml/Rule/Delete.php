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
 * Class Delete
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends \Riki\CatalogFreeShipping\Controller\Adminhtml\Rule
{


    /**
     * Delete rule
     *
     * @return void
     */
    public function execute()
    {
        $model = $this->_initRule();
        if ($model->getId()) {
            try {
                $model->delete();
                $this->messageManager->addSuccess(__('The rule has been deleted.'));
                $this->_getSession()->setFormData(false);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t delete this rule right now.'));
            }
        }

        $this->_redirect('*/*');

    }//end execute()


    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CatalogFreeShipping::rule_delete');

    }//end _isAllowed()


}//end class
