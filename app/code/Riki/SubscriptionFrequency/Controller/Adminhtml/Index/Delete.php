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
 * Class Delete
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends \Riki\SubscriptionFrequency\Controller\Adminhtml\Frequency
{
    /**
     * FrequencyFactory
     *
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * ProfileHelper
     *
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * Delete constructor.
     *
     * @param \Magento\Backend\App\Action\Context                $context          Context
     * @param \Magento\Framework\Registry                        $coreRegistry     CoreRegistry
     * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory FrequencyFactory
     * @param \Riki\Subscription\Helper\Profile\Data             $profileHelper    ProfileHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    ) {
        $this->frequencyFactory = $frequencyFactory;
        $this->profileHelper = $profileHelper;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * IsAllowed
     *
     * @return bool
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionFrequency::delete');
    }

    /**
     * Delete Execute
     *
     * @return $this
     */
    public function execute()
    {
        /**
         * ResultRedirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect ResultRedirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('frequency_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->frequencyFactory->create();
                $model->load($id);
                if ($model->getId()) {
                    $checkIsExistInProfile = $this->profileHelper->checkFrequencyIsExistedInProfile($model->getUnitFrequency(), $model->getIntervalFrequency());
                    if (!$checkIsExistInProfile) {
                        $this->messageManager->addError(__('We cannot delete the frequency exist in subscription profile'));
                        return $resultRedirect->setPath('*/*/edit', ['frequency_id' => $id]);
                    }
                    $model->delete();
                    // display success message
                    $this->messageManager->addSuccess(__('You deleted the frequency.'));
                    // go to grid
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['frequency_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a frequency to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
