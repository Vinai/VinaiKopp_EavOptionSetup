<?php

namespace VinaiKopp\EavOptionSetup\Setup;

use Magento\Eav\Api\AttributeOptionManagementInterface as AttributeOptionManagementService;
use Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use Magento\Eav\Api\Data\AttributeInterface as Attribute;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory as AttributeOptionFactory;
use Magento\Eav\Api\Data\AttributeOptionInterface as AttributeOption;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory as AttributeOptionLabelFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface as AttributeOptionLabel;
use Magento\Framework\App\State as AppState;

class EavOptionSetup
{
    const ADMIN_SCOPE_ID = 0;

    /**
     * @var AttributeOptionManagementService
     */
    private $attrOptionManagementService;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var AttributeOption[]
     */
    private $attrOptionList;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @var AttributeOptionFactory
     */
    private $attributeOptionFactory;

    /**
     * @var AttributeOptionLabelFactory
     */
    private $attributeOptionLabelFactory;

    /**
     * @var AppState
     */
    private $appState;

    public function __construct(
        AttributeRepository $attributeRepository,
        AttributeOptionManagementService $attributeOptionManagementService,
        AttributeOptionFactory $attributeOptionFactory,
        AttributeOptionLabelFactory $attributeOptionLabelFactory,
        AppState $appState
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->attrOptionManagementService = $attributeOptionManagementService;
        $this->attributeOptionFactory = $attributeOptionFactory;
        $this->attributeOptionLabelFactory = $attributeOptionLabelFactory;
        $this->appState = $appState;
    }

    /**
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param string $defaultOptionLabel
     */
    public function addAttributeOptionIfNotExists($entityTypeCode, $attributeCode, $defaultOptionLabel)
    {
        $this->addAttributeOptionIfNotExistsWithStoreLabels($entityTypeCode, $attributeCode, $defaultOptionLabel, []);
    }

    /**
     * @param string $entityTypeCode
     * @param string $attributeCode
     * @param string $defaultOptionLabel
     * @param string[] $storeScopeLabels [Store ID => "Store Label"]
     */
    public function addAttributeOptionIfNotExistsWithStoreLabels(
        $entityTypeCode,
        $attributeCode,
        $defaultOptionLabel,
        array $storeScopeLabels
    ) {
        $this->validateStoreScopeLabels($storeScopeLabels);
        $this->initClassProperties($entityTypeCode, $attributeCode);

        if (!$this->attributeHasOption($defaultOptionLabel)) {
            $this->addAttributeOption($defaultOptionLabel, $storeScopeLabels);
        }
    }

    /**
     * @param string[] $optionLabels
     */
    private function validateStoreScopeLabels(array $optionLabels)
    {
        array_map(function($storeId) use ($optionLabels) {
            if (! is_int($storeId)) {
                throw new \RuntimeException(__(
                    'Store view labels have to be mapped to a numeric store ID, found the array key "%1" for label "%2"',
                    $storeId,
                    $optionLabels[$storeId]
                ));
            }
        }, array_keys($optionLabels));
        if (isset($optionLabels[self::ADMIN_SCOPE_ID])) {
            throw new \RuntimeException(__('An admin scope option label (array key 0) is specified as a store scope label'));
        }
    }

    /**
     * @param string $entityTypeCode
     * @param string $attributeCode
     */
    private function initClassProperties($entityTypeCode, $attributeCode)
    {
        $this->initAttribute($entityTypeCode, $attributeCode);
        $this->initOptionListProperty();
    }

    /**
     * @param string $entityTypeCode
     * @param string $attributeCode
     */
    private function initAttribute($entityTypeCode, $attributeCode)
    {
        try {
            $failMessage = '';
            $this->attribute = null;
            $this->attribute = $this->attributeRepository->get($entityTypeCode, $attributeCode);
        } catch (\Exception $e) {
            $failMessage = $e->getMessage();
        }
        $this->validateAttributeWasReturned($entityTypeCode, $attributeCode, $failMessage);
    }

    private function validateAttributeWasReturned($entityTypeCode, $attributeCode, $failMessage)
    {
        if (
            !$this->attribute instanceof Attribute ||
            !$this->attribute->getAttributeId()
        ) {
            throw new \RuntimeException(__(
                "The attribute %1.%2 could not be loaded%3",
                $entityTypeCode,
                $attributeCode,
                ($failMessage ? ":\n" . $failMessage : '')
            ));
        }
    }

    private function initOptionListProperty()
    {
        $options = $this->attrOptionManagementService->getItems(
            $this->attribute->getEntityTypeId(),
            $this->attribute->getAttributeCode()
        );
        $this->attrOptionList = $options ? $options : [];
    }

    /**
     * @param string $defaultLabel
     * @return bool
     */
    private function attributeHasOption($defaultLabel)
    {
        return in_array($defaultLabel, $this->getDefaultOptionLabelsAsStrings());
    }

    /**
     * @return string[]
     */
    private function getDefaultOptionLabelsAsStrings()
    {
        return array_map(function (AttributeOption $option) {
            return $option->getLabel();
        }, $this->attrOptionList);
    }

    /**
     * @param string $defaultLabel
     * @param string[] $storeScopeLabels
     */
    private function addAttributeOption($defaultLabel, array $storeScopeLabels)
    {
        $this->workaroundIssue1405();
        $this->attrOptionManagementService->add(
            $this->attribute->getEntityTypeId(),
            $this->attribute->getAttributeCode(),
            $this->createAttributeOption($defaultLabel, $storeScopeLabels)
        );
    }

    /**
     * @param string $defaultLabel
     * @param string[] $optionLabels
     * @return AttributeOption
     */
    private function createAttributeOption($defaultLabel, array $optionLabels)
    {
        /** @var AttributeOption $option */
        $option = $this->attributeOptionFactory->create();
        $option->setLabel($defaultLabel);
        $option->setStoreLabels($this->createStoreScopeOptionLabels($optionLabels));
        $option->setSortOrder($this->getMaxAttributeSortOrder() + 100);
        return $option;
    }

    /**
     * @param string[] $optionLabels
     * @return AttributeOptionLabel[]
     */
    private function createStoreScopeOptionLabels(array $optionLabels)
    {
        return array_map(function($storeId) use ($optionLabels) {
            return $this->createOptionLabel($storeId, $optionLabels[$storeId]);
        }, array_keys($optionLabels));
    }

    /**
     * @param string $storeLabel
     * @param int $storeId
     * @return AttributeOptionLabel
     */
    private function createOptionLabel($storeLabel, $storeId)
    {
        /** @var AttributeOptionLabel $optionLabel */
        $optionLabel = $this->attributeOptionLabelFactory->create();
        $optionLabel->setLabel($storeLabel);
        $optionLabel->setStoreId($storeId);
        return $optionLabel;
    }

    /**
     * @return int
     */
    private function getMaxAttributeSortOrder()
    {
        return array_reduce($this->attrOptionList, function ($max, AttributeOption $option) {
            return max($max, $option->getSortOrder());
        }, 0);
    }

    /**
     * Reference https://github.com/magento/magento2/issues/1405
     * Remove this method once the issue is resolved that calling an Api Interface
     * method from a Module Install triggers Exception that Area Code is not set
     */
    private function workaroundIssue1405()
    {
        if (!$this->appState->getAreaCode()) {
            $this->appState->setAreaCode('adminhtml');
        }
    }
}
