<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Block\Adminhtml\Score\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Mirasvit\FraudCheck\Block\Adminhtml\Score\Edit\Renderer\Rules;
use Mirasvit\FraudCheck\Model\Score;

class Form extends WidgetForm
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var Score
     */
    protected $score;

    /**
     * @var Rules
     */
    protected $rules;

    /**
     * @param Rules       $rules
     * @param Score       $score
     * @param FormFactory $formFactory
     * @param Context     $context
     */
    public function __construct(
        Rules $rules,
        Score $score,
        FormFactory $formFactory,
        Context $context
    ) {
        $this->rules = $rules;
        $this->formFactory = $formFactory;
        $this->score = $score;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        $form = $this->formFactory->create()->setData([
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/save'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ]);

        $fieldset = $form->addFieldset('score', []);

        $fieldset->addElement($this->rules);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
