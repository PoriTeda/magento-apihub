<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml;

class Rma
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * check permission for some action
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma $subject
     * @param $result
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Rma\Block\Adminhtml\Rma $subject,
        $result
    ) {
        if (!$subject->getParentBlock() &&
            !$this->authorization->isAllowed('Riki_Rma::rma_return_actions_save')
        ) {
            $subject->removeButton('add');
        }

        return $result;
    }
}
