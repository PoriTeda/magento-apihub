<?php
namespace Riki\Customer\Model\EnquiryHeader;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Riki\Customer\Model\ResourceModel\EnquiryHeader\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Riki\Customer\Model\ResourceModel\EnquiryHeader\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    )
    {
        $this->collectionFactory = $collectionFactory;

        $this->collection = $this->collectionFactory->create();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }


    /**
     * GetData
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $model) {
            $data = $model->getData();
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }

        return $this->loadedData;
    }
}
