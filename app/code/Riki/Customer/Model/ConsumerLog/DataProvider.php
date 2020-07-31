<?php
namespace Riki\Customer\Model\ConsumerLog;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Riki\Customer\Model\ConsumerLogFactory
     */
    protected $consumerLogFactory;


    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Customer\Model\ResourceModel\ConsumerLog\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Riki\Customer\Model\ConsumerLogFactory $consumerLogFactory,
        \Riki\Customer\Model\ResourceModel\ConsumerLog\CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    )
    {
        $this->consumerLogFactory= $consumerLogFactory;
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
        /** @var \Riki\Customer\Model\ConsumerLog $model */
        foreach ($items as $model) {
            $data = $model->getData();
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }

        return $this->loadedData;
    }
}
