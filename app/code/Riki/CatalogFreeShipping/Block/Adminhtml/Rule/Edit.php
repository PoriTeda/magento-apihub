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

namespace Riki\CatalogFreeShipping\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * Class Edit
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Edit extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;


    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context  context
     * @param \Magento\Framework\Registry           $registry registry
     * @param array                                 $data     data
     *
     * @return self
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);

    }//end __construct()


    /**
     * Department edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Riki_CatalogFreeShipping';
        $this->_controller = 'adminhtml_rule';

        parent::_construct();

        if ($this->_isAllowedAction('Riki_CatalogFreeShipping::rule_save')) {
            $this->buttonList->update('save', 'label', __('Save Rule'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                 'label'          => __('Save and Continue Edit'),
                 'class'          => 'save',
                 'data_attribute' => [
                                      'mage-init' => [
                                                      'button' => [
                                                                   'event'  => 'saveAndContinueEdit',
                                                                   'target' => '#edit_form',
                                                                  ],
                                                     ],
                                     ],
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }//end if

        if ($this->_isAllowedAction('Riki_CatalogFreeShipping::rule_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete'));
        } else {
            $this->buttonList->remove('delete');
        }

    }//end _construct()


    /**
     * Retrieve template object
     *
     * @return \Magento\Newsletter\Model\Template
     */
    public function getModel()
    {
        return $this->coreRegistry->registry('_current_rule');

    }//end getModel()


    /**
     * Return edit flag for block
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEditMode()
    {
        if ($this->getModel()->getId()) {
            return true;
        }

        return false;

    }//end getEditMode()


    /**
     * Check permission for passed action
     *
     * @param string $resourceId resource id
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);

    }//end _isAllowedAction()


    /**
     * Return header text for form
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->getEditMode()) {
            return __('Edit Rule');
        }

        return __('New Rule');

    }//end getHeaderText()


    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);

    }//end _getSaveAndContinueUrl()


}//end class
