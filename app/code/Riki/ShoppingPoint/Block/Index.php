<?php
/**
 * Riki Sales calculate cut off date for Shipment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShoppingPoint\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ShoppingPoint\Block;
/**
 * Class Index
 *
 * @category  RIKI
 * @package   Riki\ShoppingPoint\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    protected  $session;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Magento\Customer\Model\Session $session
    )
    {
        $this->session = $session;
        parent::__construct($context, $data);

    }

    /**
     * Set template variables
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setBackUrl($this->getUrl('customer/account/'));
        return parent::_toHtml();
    }

    /**
     * @return mixed
     */
    public function getRewardUserRedeem()
    {
        return $this->getCustomer()->getRewardUserRedeem();
    }

    /**
     * @return mixed
     */
    public function getRewardUserSettingHtmlSelect()
    {
        $name = 'reward_user_setting';
        $id = 'reward_user_setting';
        $title = 'User shopping point setting';
        $defValue = $this->getCustomer()->getRewardUserSetting();
        $options = array();
        $options[] = array('label' =>__('Please select option'), 'value'=>'');
        $options[] = array('label' =>__('Not use point'), 'value'=>0);
        $options[] = array('label' =>__('Automatically use all points'), 'value'=>1);
        $options[] = array('label' =>__('Automatically redeem a specified maximum number of points'), 'value'=>2);

        $html = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setName(
            $name
        )->setId(
            $id
        )->setTitle(
            __($title)
        )->setValue(
            $defValue
        )->setOptions(
            $options
        )->setExtraParams(
            'data-validate="{\'validate-select\':true}"'
        )->getHtml();

        return $html;
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    protected function getCustomer()
    {
        return $this->session->getCustomer();
    }

}
