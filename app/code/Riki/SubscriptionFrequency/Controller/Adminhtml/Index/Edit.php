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
 * Class Edit
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Edit extends \Riki\SubscriptionFrequency\Controller\Adminhtml\Frequency
{
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
     * PageFactory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * FrequencyFactory
     *
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context Context
     * @param \Magento\Framework\Registry $coreRegistry CoreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory ResultPageFactory
     * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory FrequencyFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->frequencyFactory = $frequencyFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit CMS block
     *
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('frequency_id');
        $model = $this->frequencyFactory->create();

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This frequency no longer exists.'));
                /**
                 * ResultRedirect
                 *
                 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect ResultRedirect
                 */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        // 3. Set entered data if was error when we do save
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->_coreRegistry->register('frequency_item', $model);

        /**
         * ResultPage
         *
         * @var \Magento\Backend\Model\View\Result\Page $resultPage ResultPage
         */
        $resultPage = $this->resultPageFactory->create();

        // 5. Build edit form
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Frequency') : __('New Frequency'),
            $id ? __('Edit Frequency') : __('New Frequency')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Frequencies'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Frequency') : __('New Frequency'));
        return $resultPage;
    }
}
