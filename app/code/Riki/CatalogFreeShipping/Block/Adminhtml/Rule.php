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

namespace Riki\CatalogFreeShipping\Block\Adminhtml;

/**
 * Class Rule
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Rule extends \Magento\Backend\Block\Widget\Grid\Container
{


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_rule';
        $this->_blockGroup = 'Riki_CatalogFreeShipping';
        $this->_headerText = __('Rule Management');
        parent::_construct();
        if ($this->_isAllowedAction('Riki_CatalogFreeShipping::rule_save')) {
            $this->buttonList->update('add', 'label', __('Add New Rule'));
        } else {
            $this->buttonList->remove('add');
        }

    }//end _construct()


    /**
     * Check permission
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
     * Get create url
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newAction');

    }//end getCreateUrl()


}//end class
