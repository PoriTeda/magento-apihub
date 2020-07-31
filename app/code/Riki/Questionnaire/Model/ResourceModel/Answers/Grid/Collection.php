<?php
namespace Riki\Questionnaire\Model\ResourceModel\Answers\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Collection
 * @package Riki\Questionnaire\Model\ResourceModel\Answers\Grid
 */
class Collection extends SearchResult
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Collection constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        $resourceModel
    ) {
        $this->request = $request;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['questionnaire' => $this->getTable('riki_enquete')],
            'main_table.enquete_id = questionnaire.enquete_id',
            ['linked_product_sku', 'code', 'name',
            ]
        );

        $this->getSelect()->joinLeft(
            ['order' => $this->getTable('sales_order')],
            'main_table.entity_id = order.entity_id',
            ['increment_id']
        );

        $this->getSelect()->joinLeft(
            ['profile' => $this->getTable('subscription_profile')],
            'main_table.entity_id = profile.profile_id',
            ['profile_id']
        );
        
        return $this;
    }

    /**
     * Add field filter to collection
     *
     * @param array|string $field
     * @param null $condition
     *
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        switch ($field) {
            case 'answer_id':
                $field = 'main_table.answer_id';
                break;
            case 'name':
                $field = 'questionnaire.name';
                break;
            case 'code':
                $field = 'questionnaire.code';
                break;
            case 'customer_id':
                $field = 'main_table.customer_id';
                break;
            case 'increment_id':
                $field = 'order.increment_id';
                break;
            case 'created_at':
                $field = 'main_table.created_at';
                break;
            case 'updated_at':
                $field = 'main_table.updated_at';
                break;
            case 'entity_id':
                if ($this->_hasProfileTypeFilter()) {
                    $field = 'main_table.entity_id';
                } else {
                    $field = 'order.increment_id';
                }
                break;
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Check if filters has disengagement type
     *
     * @return bool
     */
    private function _hasProfileTypeFilter()
    {
        $filters = $this->request->getParam('filters');
        if (isset($filters['entity_type']) && is_array($filters['entity_type'])) {
            foreach ($filters['entity_type'] as $type) {
                if ($type == \Riki\Questionnaire\Model\Answers::QUESTIONNAIRE_ANSWER_TYPE_PROFILE) {
                    return true;
                }
            }
        }
        return false;
    }
}
