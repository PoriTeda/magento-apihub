<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

class History
{
    /**
     * @var \Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit
     */
    protected $editPlugin;

    /**
     * History constructor.
     *
     * @param \Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit $editPlugin
     */
    public function __construct(
        \Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit $editPlugin
    ){
        $this->editPlugin = $editPlugin;
    }

    /**
     * Extend setChild
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\History $subject
     * @param $alias
     * @param $block
     *
     * @return array
     */
    public function beforeSetChild(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\History $subject,
        $alias,
        $block
    ){
        if ($alias != 'submit_button') {
            return [$alias, $block];
        }

        if (!$subject->getAuthorization()->isAllowed('Riki_Rma::rma_return_actions_reject')) {
            return [$alias, $block];
        }

        /** @var \Magento\Backend\Block\Template $newBlock */
        $newBlock = $subject->getLayout()->createBlock(\Magento\Backend\Block\Template::class);
        $newBlock->setTemplate('Riki_Rma::rma/edit/general/submit-button.phtml');
        $newBlock->setChild('submit_button', $block);

        /** @var \Magento\Backend\Block\Widget\Button $button */
        $button = $subject->getLayout()->createBlock(\Magento\Backend\Block\Widget\Button::class);
        $button->setData([
            'label' => __('Submit Comment & Reject'),
            'class' => 'action-save action-secondary',
            'onclick' => 'jQuery("#edit_form").prop("action", "' . $this->editPlugin->getRejectUrl(['id' => $this->editPlugin->getRmaId()]) . '").submit()'
        ]);
        $newBlock->setChild('submit_reject_button', $button);

        return [$alias, $newBlock];
    }


}