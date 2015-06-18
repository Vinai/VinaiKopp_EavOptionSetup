<?php


namespace VinaiKopp\EavOptionSetup\Setup;

use Magento\Eav\Model\Resource\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Eav\Model\Resource\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\SetupInterface;

class EavOptionSetup
{
    const ADMIN_ID = 0;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var OptionCollectionFactory
     */
    private $attrOptionCollectionFactory;

    /**
     * @var OptionCollection
     */
    private $attrOptionCollection;

    /**
     * @var int
     */
    private $attributeId;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        OptionCollectionFactory $attrOptionCollectionFactory,
        SetupInterface $setup
    ) {
        $this->eavSetup = $eavSetupFactory->create(['setup' => $setup]);
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
    }

    /**
     * @param $entityTypeCode
     * @param $attributeCode
     * @param array $optionLabels
     */
    public function addAttributeOptionIfNotExists($entityTypeCode, $attributeCode, array $optionLabels)
    {
        $this->validateOptionLabels($optionLabels);
        $this->initClassPropertiesForAttribute($entityTypeCode, $attributeCode);

        if (!$this->attributeHasOption($optionLabels)) {
            $this->addAttributeOption($optionLabels);
        }
    }

    private function validateOptionLabels(array $optionLabels)
    {
        if (!isset($optionLabels[self::ADMIN_ID])) {
            throw new \RuntimeException(__('Required admin scope option label (array key 0) is missing'));
        }
    }

    private function initClassPropertiesForAttribute($entityTypeCode, $attributeCode)
    {
        $this->initAttributeId($entityTypeCode, $attributeCode);
        $this->initAttributeOptionCollection($this->attributeId);
    }

    private function initAttributeId($entityTypeCode, $attributeCode)
    {
        $attributeId = $this->eavSetup->getAttributeId($entityTypeCode, $attributeCode);
        if (!$attributeId) {
            throw new \RuntimeException(
                __('Unknown attribute %1.%2', $entityTypeCode, $attributeCode)
            );
        }
        $this->attributeId = $attributeId;
    }

    private function initAttributeOptionCollection($attributeId)
    {
        $this->attrOptionCollection = $this->attrOptionCollectionFactory->create();
        $this->attrOptionCollection->setAttributeFilter($attributeId);
        $this->attrOptionCollection->setStoreFilter(self::ADMIN_ID);
    }

    private function attributeHasOption(array $optionLabel)
    {
        return in_array(
            $optionLabel[self::ADMIN_ID],
            $this->getAdminScopeOptionLabels()
        );
    }

    private function getAdminScopeOptionLabels()
    {
        return $this->attrOptionCollection->getColumnValues('value');
    }

    private function addAttributeOption(array $optionLabels)
    {
        $optionDefinition = $this->getOptionDefinition($optionLabels);
        $this->eavSetup->addAttributeOption($optionDefinition);
    }

    private function getOptionDefinition(array $optionLabels)
    {
        return [
            'attribute_id' => $this->attributeId,
            'order' => ['x' => $this->getMaxAttributeSortOrder() + 100],
            'value' => ['x' => $optionLabels]
        ];
    }

    private function getMaxAttributeSortOrder()
    {
        $columnValues = $this->attrOptionCollection->getColumnValues('sort_order');
        return empty($columnValues) ? 0 : max($columnValues);
    }
}
