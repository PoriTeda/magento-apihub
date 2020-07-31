<?php

namespace Riki\Catalog\Plugin\Model;

class ExternalVideoResourceBackend
{
    /**
     * Change product gallery data
     *      Case: duplicate a product which has image, try to edit duplicated product
     *            Magento return 4 images for duplicated product (same data but only 1 image is correct data)
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Backend\Media $subject
     * @param $result
     * @return array
     */
    public function afterLoadProductGalleryByAttributeId(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Backend\Media $subject,
        $result
    ) {
        /*correct result will return for product - has removed duplicated data*/
        $correctResult = [];

        if ($result) {
            /*remove duplicate image data for duplicated product*/

            /*list gallery id*/
            $listId = [];

            foreach  ($result as $value) {
                if (!empty($value['value_id']) && !in_array($value['value_id'], $listId)) {
                    /*push this gallery id to return id list*/
                    array_push($listId, $value['value_id']);

                    /*push this record data to return value*/
                    array_push($correctResult, $value);
                }
            }
        }

        return $correctResult;
    }
}
