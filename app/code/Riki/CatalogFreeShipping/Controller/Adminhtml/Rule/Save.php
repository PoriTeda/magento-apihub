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

use Magento\Framework\Exception\LocalizedException;

/**
 * Class Save
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Riki\CatalogFreeShipping\Controller\Adminhtml\Rule
{


    /**
     * Execute
     *
     * @return $this|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /*
            * @var \Magento\Framework\App\Request\Http $request
        */

        $result  = $this->initRedirectResult();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $result->setUrl($this->getUrl('*/rule'));
            return $result;
        }

        $result->setUrl($this->getUrl('*/rule/newAction'));

        $model = $this->_initRule();

        if (!$this->isRuleExist($model)) {
            $this->messageManager->addError(__('This rule does not exist.'));
            return $result->setUrl($this->getUrl('*/*'));
        }

        try {
            $post = $request->getPostValue();
            $this->prepareBannerModelData($model, $post);
            $model->save();

            $this->messageManager->addSuccess(__('The rule has been saved.'));

            $this->_getSession()->setFormData(false);

            if ($request->getParam('back') === 'edit') {
                $result->setUrl($this->getUrl('*/rule/edit', ['id' => $model->getId()]));
            } else {
                $result->setUrl($this->getUrl('*/*'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError(nl2br($e->getMessage()));
            $this->_getSession()->setData('riki_catalogfs_rule_form_data', $request->getParams());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while saving this rule.'));
            $this->_getSession()->setData('riki_catalogfs_rule_form_data', $request->getParams());
        }//end try

        return $result;

    }//end execute()


    /**
     * Check if rule exist
     *
     * @param \Riki\CatalogFreeShipping\Model\Rule $model rule
     *
     * @return bool
     */
    protected function isRuleExist(\Riki\CatalogFreeShipping\Model\Rule $model)
    {
        $id = $this->getRequest()->getParam('id');

        if(!$model->getId() && $id)
            return false;

        return true;

    }//end isRuleExist()


    /**
     * Prepare rule model data
     *
     * @param \Riki\CatalogFreeShipping\Model\Rule $model rule
     * @param array                                $data  data
     *
     * @return void
     */
    protected function prepareBannerModelData(\Riki\CatalogFreeShipping\Model\Rule $model, array $data)
    {
        if (!empty($data)) {
            $model->addData($data);
            $this->_getSession()->setFormData($data);
        }

    }//end prepareBannerModelData()


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
