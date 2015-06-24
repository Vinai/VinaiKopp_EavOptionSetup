<?php


namespace VinaiKopp\EavOptionSetup\Test;

use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use VinaiKopp\EavOptionSetup\Setup\EavOptionSetup;

/**
 * @covers \VinaiKopp\EavOptionSetup\Setup\EavOptionSetup
 */
class EavOptionSetupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EavOptionSetup
     */
    private $optionSetup;

    /**
     * @var AttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAttributeRepository;

    /**
     * @var AttributeOptionManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAttributeOptionManagementService;

    /**
     * @var AttributeOptionInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAttributeOptionFactory;

    /**
     * @var AttributeOptionLabelInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAttributeOptionLabelFactory;

    /**
     * @param string $testLabel
     * @param int $testSortOrder
     * @return AttributeOptionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockOptionLabel($testLabel, $testSortOrder)
    {
        $mockOption = $this->getMock(AttributeOptionInterface::class);
        $mockOption->method('getLabel')->willReturn($testLabel);
        $mockOption->method('getSortOrder')->willReturn($testSortOrder);
        return $mockOption;
    }

    private function setSpecifiedAttributeExistsFixture()
    {
        $dummyAttributeId = 111;
        $mockAttribute = $this->getMock(AttributeInterface::class);
        $mockAttribute->method('getAttributeId')->willReturn($dummyAttributeId);
        $this->mockAttributeRepository->method('get')->willReturn($mockAttribute);
    }

    private function expectNewOptionWithStoreLabelToBeCreated()
    {
        $mockOption = $this->getMock(AttributeOptionInterface::class);
        $mockOption->expects($this->once())->method('setStoreLabels')->willReturnCallback(function ($args) {
            $this->assertInternalType('array', $args);
            $this->assertCount(1, $args);
            $this->assertContainsOnlyInstancesOf(AttributeOptionLabelInterface::class, $args);
        });
        $this->mockAttributeOptionFactory->method('create')->willReturn($mockOption);
    }

    protected function setUp()
    {
        $this->mockAttributeRepository = $this->getMock(AttributeRepositoryInterface::class);
        $this->mockAttributeOptionManagementService = $this->getMock(AttributeOptionManagementInterface::class);
        $this->mockAttributeOptionFactory = $this->getMock(AttributeOptionInterfaceFactory::class, ['create']);
        $this->mockAttributeOptionLabelFactory = $this->getMock(AttributeOptionLabelInterfaceFactory::class, ['create']);
        
        $this->optionSetup = new EavOptionSetup(
            $this->mockAttributeRepository,
            $this->mockAttributeOptionManagementService,
            $this->mockAttributeOptionFactory,
            $this->mockAttributeOptionLabelFactory
        );
    }

    /**
     * @test
     */
    public function itShouldThrowIfAdminLabelIsSpecifiedAsStoreScopeLabel()
    {
        $this->setExpectedException(\RuntimeException::class);
        
        $this->optionSetup->addAttributeOptionIfNotExistsWithStoreLabels(
            'entity_type',
            'attribute_code',
            'Default Store Label',
            [0 => 'Store Scope Label with Admin Scope ID']
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionIfANonNumericStoreIdWasSpecified()
    {
        $this->setExpectedException(\RuntimeException::class);

        $this->optionSetup->addAttributeOptionIfNotExistsWithStoreLabels(
            'entity_type',
            'attribute_code',
            'Default Store Label',
            ['test' => 'Store Scope Label with Admin Scope ID']
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionIfTheAttributeIsNotKnown()
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->mockAttributeRepository->method('get')->willThrowException(new \Exception('Test Exception'));
        $this->optionSetup->addAttributeOptionIfNotExists(
            'entity_type',
            'attribute_code',
            'Default Option Label'
        );
    }

    /**
     * @test
     * @dataProvider unexpectedReturnValueProvider
     */
    public function itShouldThrowAnExceptionIfTheRepositoryReturnsAUnexpectedResult($returnValue)
    {
        $this->setExpectedException(\RuntimeException::class);
        $this->mockAttributeRepository->method('get')->willReturn($returnValue);
        $this->optionSetup->addAttributeOptionIfNotExists(
            'entity_type',
            'attribute_code',
            'Default Option Label'
        );
    }

    public function unexpectedReturnValueProvider()
    {
        return [
            'null' => [null],
            'string' => ['a string'],
            'empty attribute' => [$this->getMock(AttributeInterface::class)]
        ];
    }

    /**
     * @test
     */
    public function itShouldNotAddKnownAttributes()
    {
        $mockAttribute = $this->getMock(AttributeInterface::class);
        $mockAttribute->method('getAttributeId')->willReturn(111);
        
        $this->mockAttributeRepository->method('get')->willReturn($mockAttribute);

        $this->mockAttributeOptionManagementService->method('getItems')
            ->willReturn([
                $this->createMockOptionLabel('Option 1', 100),
                $this->createMockOptionLabel('Option 2', 200),
                $this->createMockOptionLabel('Option 3', 300),
            ]);
        
        $this->mockAttributeOptionManagementService->expects($this->never())->method('add');
        
        $this->optionSetup->addAttributeOptionIfNotExists('entity_code', 'attribute_code', 'Option 2');
    }

    /**
     * @test
     */
    public function itShouldAddKnownAttributes()
    {
        $this->setSpecifiedAttributeExistsFixture();
        
        $this->mockAttributeOptionFactory->method('create')->willReturn(
            $this->getMock(AttributeOptionInterface::class)
        );

        $this->mockAttributeOptionManagementService->method('getItems')
            ->willReturn([
                $this->createMockOptionLabel('Option 1', 100),
                $this->createMockOptionLabel('Option 2', 200),
                $this->createMockOptionLabel('Option 3', 300),
            ]);

        $this->mockAttributeOptionManagementService->expects($this->once())->method('add');
        
        $this->optionSetup->addAttributeOptionIfNotExists('entity_code', 'attribute_code', 'Option 4');
    }

    /**
     * @test
     */
    public function itShouldAddStoreLabelInstances()
    {
        $this->setSpecifiedAttributeExistsFixture();

        $this->expectNewOptionWithStoreLabelToBeCreated();
        
        $this->mockAttributeOptionLabelFactory->method('create')->willReturn(
            $this->getMock(AttributeOptionLabelInterface::class)
        );

        $this->mockAttributeOptionManagementService->method('getItems')
            ->willReturn([
                $this->createMockOptionLabel('Option 1', 100),
                $this->createMockOptionLabel('Option 2', 200),
                $this->createMockOptionLabel('Option 3', 300),
            ]);

        $this->mockAttributeOptionManagementService->expects($this->once())->method('add');

        $testStoreId = 1;
        $this->optionSetup->addAttributeOptionIfNotExistsWithStoreLabels(
            'entity_code',
            'attribute_code',
            'Option 4',
            [$testStoreId => 'Option 4 Store 1 Label']
        );
    }
}
