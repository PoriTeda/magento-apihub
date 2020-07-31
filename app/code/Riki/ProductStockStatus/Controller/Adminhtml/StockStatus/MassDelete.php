<?php
/**
 * Schedules Controller
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductStockStatus\Controller\Adminhtml\StockStatus;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;
    /**
     * @var \Riki\ProductStockStatus\Model\ResourceModel\StockStatus\CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    protected $_attributeRepository;

    protected $_updateProduct;

    /**
     * MassDelete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Riki\ProductStockStatus\Model\ResourceModel\StockStatus\CollectionFactory $collectionFactory
     * @param \Magento\Eav\Model\AttributeRepository $attributeRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Riki\ProductStockStatus\Model\ResourceModel\StockStatus\CollectionFactory $collectionFactory,
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        \Riki\ProductStockStatus\Model\UpdateProduct $updateProduct
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_updateProduct = $updateProduct;
        parent::__construct($context);
    }

    /**
     * Delete courses
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item)
        {
            try {
                $item->delete();
                $this->_updateProduct->setProductDefaultValue($item->getId());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
            
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ProductStoctStatus::stockstatus');
    }
}
