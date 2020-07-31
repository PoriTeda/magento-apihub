<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Block\Adminhtml;

/**
 * Class Rma
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Rma extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_controller = 'adminhtml_rma';
        $this->_blockGroup = 'Riki_TmpRma';
        $this->_headerText = __('Manage Temp Return');
        parent::_construct();
        if ($this->_authorization->isAllowed('Riki_TmpRma::rma_actions_create')) {
            $this->buttonList->update('add', 'label', __('Add New Return'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newAction');
    }
}