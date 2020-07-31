<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ReceiveCvsPayment\Block\Adminhtml\Importing\Edit;
/**
 * Class Tabs
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('importing_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Importing information'));
    }
}
