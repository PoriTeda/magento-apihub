<?php
namespace Riki\Questionnaire\Model\Config\Source;
use Magento\Framework\Option\ArrayInterface;
use Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory;

/**
 * Class QuestionnaireOption
 * @package Riki\Questionnaire\Model\Config\Source
 */
class QuestionnaireOption implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $questionnaireCollection;

    /**
     * QuestionnaireOption constructor.
     *
     * @param CollectionFactory $collection
     */
    public function __construct(
        CollectionFactory $collection
    ) {
        $this->questionnaireCollection = $collection;
    }

    /**
     * Get Array Questionnaire id
     *
     * @return array
     */
    public function toOptionArray()
    {
        $option = [];
        $option[] = [
            'value' => '',
            'label' => __('------Please select default questionnaire------')
        ];

        $collection = $this->questionnaireCollection->create();
        
        $collection->addFieldToFilter('is_enabled', ['eq' => \Riki\Questionnaire\Model\Questionnaire::STATUS_ENABLED])
            ->addFieldToSelect(['enquete_id','code','name']);

        if ($collection->getSize()) {
            foreach ($collection as $item) {
                /** @var \Riki\Questionnaire\Model\Questionnaire $item */
                $option[] = [
                    'value' => $item->getId(),
                    'label' => $item->getId() .'-'. $item->getName()
                ];
            }
        }

        return $option;
    }
}