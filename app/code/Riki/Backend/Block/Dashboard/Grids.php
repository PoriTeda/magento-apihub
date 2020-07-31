<?php
/**
 * *
 *  ImportExport
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\ImportExport
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 * /
 */



namespace Riki\Backend\Block\Dashboard;
/**
 * *
 *  Class Grids
 *
 *  @category RIKI
 *  @package  Riki\Riki\Backend\Block\Dashboard
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Grids extends \Magento\Backend\Block\Dashboard\Grids
{
    /**
     * Extended layout
     * 
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (isset($this->_tabs['customers'])) {
            $customerTab = $this->_tabs['customers'];
            $customerTab->setLabel(__('Customers With High Orders'));
        }

        return $this;
    }
}
