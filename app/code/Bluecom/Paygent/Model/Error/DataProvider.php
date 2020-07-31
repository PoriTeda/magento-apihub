<?php
namespace Bluecom\Paygent\Model\Error;

use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Bluecom\Paygent\Model\ResourceModel\Error\CollectionFactory
     */
    protected $collectionFactory;


    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Bluecom\Paygent\Model\ResourceModel\Error\CollectionFactory $collectionFactory,
        \Magento\Framework\App\RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
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
        /** @var \Bluecom\Paygent\Model\Error $model */
        foreach ($items as $model) {
            $data = $model->getData();
            if (isset($data['ip']) && $data['ip'] != '') {
                $data['ip'] = long2ip($data['ip']);
            }
            $this->loadedData[$model->getId()] = [
                'data' => $data
            ];
        }
        return $this->loadedData;
    }
}
