<?php
/**
 * SubscriptionFrequency
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit;

/**
 * Class Form
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * PrepareForm
     *
     * @return $this
     */
    protected function _prepareForm() // @codingStandardsIgnoreLine
    {
        /**
         * Form
         *
         * @var \Magento\Framework\Data\Form $form Form
         */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
