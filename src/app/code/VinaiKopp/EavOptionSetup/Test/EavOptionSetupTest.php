<?php


namespace VinaiKopp\EavOptionSetup\Test;

use Magento\Eav\Model\Resource\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Eav\Model\Resource\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use VinaiKopp\EavOptionSetup\Setup\EavOptionSetup;

class EavOptionSetupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EavSetup|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockEavSetup;

    /**
     * @var EavOptionSetup
     */
    private $optionSetup;

    /**
     * @var OptionCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockOptionCollection;

    protected function setUp()
    {
        $this->mockEavSetup = $this->getMock(EavSetup::class, [], [], '', false);
        $mockEavSetupFactory = $this->getMock(EavSetupFactory::class, ['create'], [], '', false);
        $mockEavSetupFactory->method('create')->willReturn($this->mockEavSetup);
        
        $this->mockOptionCollection = $this->getMock(OptionCollection::class, [], [], '', false);
        $mockOptionCollectionFactory = $this->getMock(OptionCollectionFactory::class, ['create'], [], '', false);
        $mockOptionCollectionFactory->method('create')->willReturn($this->mockOptionCollection);
        
        $this->optionSetup = (new ObjectManager($this))->getObject(EavOptionSetup::class, [
            'eavSetupFactory' => $mockEavSetupFactory,
            'attrOptionCollectionFactory' => $mockOptionCollectionFactory
        ]);
    }

    /**
     * @test
     */
    public function itShouldThrowIfNoAdminLabelIsSpecified()
    {
        $this->setExpectedException(\RuntimeException::class);
        
        $this->optionSetup->addAttributeOptionIfNotExists(
            'entity_type',
            'attribute_code',
            [1 => 'frontend-store-label']
        );
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionIfTheAttributeIsNotKnown()
    {
        $this->setExpectedException(\RuntimeException::class);
        
        $this->mockEavSetup->method('getAttributeId')->willReturn(null);
        $this->optionSetup->addAttributeOptionIfNotExists(
            'entity_type',
            'attribute_code',
            [0 => 'admin-store-label']
        );
    }

    /**
     * @test
     */
    public function itShouldNotAddKnownAttributes()
    {
        $this->mockEavSetup->method('getAttributeId')->willReturn(111);
        $this->mockOptionCollection->method('getColumnValues')
            ->willReturnMap([
                ['value', ['Option 1', 'Option 2', 'Option 3']],
                ['sort_order', [100, 200, 300]],
            ]);
        
        $this->mockEavSetup->expects($this->never())->method('addAttributeOption');
        
        $this->optionSetup->addAttributeOptionIfNotExists('entity_code', 'attribute_code', [0 => 'Option 1']);
    }

    /**
     * @test
     */
    public function itShouldAddKnownAttributes()
    {
        $this->mockEavSetup->method('getAttributeId')->willReturn(111);
        $this->mockOptionCollection->method('getColumnValues')
            ->willReturnMap([
                ['value', ['Option 1', 'Option 2', 'Option 3']],
                ['sort_order', [100, 200, 300]],
            ]);
        
        $this->mockEavSetup->expects($this->once())->method('addAttributeOption');
        
        $this->optionSetup->addAttributeOptionIfNotExists('entity_code', 'attribute_code', [0 => 'Option 4']);
    }
}
