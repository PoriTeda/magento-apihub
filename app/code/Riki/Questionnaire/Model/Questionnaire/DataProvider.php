<?php
namespace Riki\Questionnaire\Model\Questionnaire;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Riki\Questionnaire\Model\QuestionnaireFactory
     */
    protected $questionnaireFactory;


    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Riki\Questionnaire\Model\QuestionnaireFactory $questionnaireFactory,
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    )
    {
        $this->questionnaireFactory = $questionnaireFactory;
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;

        $this->collection = $this->collectionFactory->create();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }


    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var \Riki\Questionnaire\Model\Questionnaire $model */
        foreach ($items as $model) {
            $data = $model->getData();
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }

        return $this->loadedData;
    }
}
