<?php
namespace Riki\Rma\Plugin\Rma\Controller\Adminhtml\Rma;

class SaveNew
{
    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var \Riki\Rma\Helper\Rma\Item
     */
    protected $rmaItemHelper;

    /**
     * SaveNew constructor.
     * @param \Riki\Rma\Helper\Rma\Item $rmaItemHelper
     * @param \Magento\Framework\Message\Manager $messageManager
     */
    public function __construct(
        \Riki\Rma\Helper\Rma\Item $rmaItemHelper,
        \Magento\Framework\Message\Manager $messageManager
    )
    {
        $this->rmaItemHelper = $rmaItemHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * Extend execute()
     *
     * @param \Magento\Rma\Controller\Adminhtml\Rma $subject
     * @return array
     */
    public function beforeExecute(\Magento\Rma\Controller\Adminhtml\Rma $subject)
    {
        $request = $subject->getRequest();
        $postValues = $request->getPostValue();
        if (isset($postValues['items'])) {
            $defaultData = $this->rmaItemHelper->getDefaultData();

            $newItems = [];

            $rmaId = (int)$request->getParam('rma_id');

            foreach ($postValues['items'] as $key => $item) {

                $key = $rmaId? $key : $key . '_';

                $newItems[$key] = array_merge($item, $defaultData);
            }
            $request->setPostValue('items', $newItems);
        }

        return [];
    }
}