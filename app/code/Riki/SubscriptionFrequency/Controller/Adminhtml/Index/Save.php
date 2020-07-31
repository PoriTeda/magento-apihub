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
namespace Riki\SubscriptionFrequency\Controller\Adminhtml\Index;

/**
 * Class Save
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Riki\SubscriptionFrequency\Controller\Adminhtml\Frequency
{
    /**
     * FrequencyFactory
     *
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context Context
     * @param \Magento\Framework\Registry $coreRegistry CoreRegistry
     * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory FrequencyFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
    )
    {
        $this->frequencyFactory = $frequencyFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * IsAllowed
     *
     * @return bool
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionFrequency::save');
    }

    /**
     * Save Execute
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * ResultRedirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect ResultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();

        // check if data sent
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('frequency_id');
            $model = $this->frequencyFactory->create()->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This frequency no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $frequencyOld = $model->getResource()->checkDuplicateFrequency($data['frequency_unit'], $data['frequency_interval']);
            if (count($frequencyOld) > 0 && $id != $frequencyOld['frequency_id']) {
                $this->messageManager->addError('This frequency is existed. It could not be added.');
                // save data in session
                $this->_session->setFormData($data);
                // redirect to edit form
                return $resultRedirect->setPath('*/*/edit', ['frequency_id' => $this->getRequest()->getParam('frequency_id')]);
            }

            // init model and set data

            $model->setData($data);

            // try to save it
            try {
                // save the data
                $model->save();
                // display success message
                $this->messageManager->addSuccess(__('You saved the frequency.'));
                // clear previously saved data from session
                $this->_session->setFormData(false);

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['frequency_id' => $model->getId()]);
                }
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // save data in session
                $this->_session->setFormData($data);
                // redirect to edit form
                return $resultRedirect->setPath('*/*/edit', ['frequency_id' => $this->getRequest()->getParam('frequency_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
