<?php

namespace Riki\Subscription\Block\Adminhtml\Landing\Page\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Class Form
 *
 * @package Riki\Subscription\Block\Adminhtml\Landing\Page\Edit
 */
class Form extends Generic
{
    /**
     * Prepare form
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' =>
                [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                ]
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
