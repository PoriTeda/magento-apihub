<?php
namespace Riki\Questionnaire\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Riki\Questionnaire\Model\Questionnaire;
use Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory;

/**
 * Class QuestionnaireOption
 * @package Riki\Questionnaire\Model\Config\Source
 */
class DisengagementQuestionnaire implements ArrayInterface
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

        $collection = $this->questionnaireCollection->create();

        $collection
            ->addFieldToFilter('is_enabled', ['eq' => Questionnaire::STATUS_ENABLED])
            ->addFieldToFilter('enquete_type', ['eq' => Questionnaire::DISENGAGEMENT_QUESTIONNAIRE])
            ->addFieldToSelect(['enquete_id','name']);
        if ($collection->getSize()) {
            foreach ($collection as $item) {
                /** @var Questionnaire $item */
                $option[] = [
                    'value' => $item->getId(),
                    'label' => $item->getId() .' - '. $item->getName()
                ];
            }
        }

        return $option;
    }
}
