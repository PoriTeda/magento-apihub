<?php
namespace Riki\Prize\Model\Prize;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $prize;


    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Riki\Prize\Model\ResourceModel\Prize\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        \Riki\Prize\Model\ResourceModel\Prize\CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    )
    {
        $this->prize = $prizeFactory;
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
        /** @var \Riki\Blacklisted\Model\Blacklisted $model */
        foreach ($items as $model) {
            $data = $model->getData();
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }

        return $this->loadedData;
    }
}
