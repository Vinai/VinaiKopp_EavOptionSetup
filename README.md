Magento2 EavOptionSetup  
=======================

This Magento2 extension contains a class to easily add EAV attribute options only if the attribute does not already contain an option with the same admin scope label.

Facts
-----
- [extension on GitHub](https://github.com/Vinai/VinaiKopp_EavOptionSetup)

Description
-----------
Usage: Require EavOptionSetupFactory using DI. Only one public method is provided: `$optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, [0 => 'Foo']);`

The array of labels are store IDs to Label mappings. The admin scope store label is required, all frontend store labels are optional.

Usage Example:

```php
<?php


namespace Example\EavOptionTest\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use VinaiKopp\EavOptionSetup\Setup\EavOptionSetup;
use VinaiKopp\EavOptionSetup\Setup\EavOptionSetupFactory;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavOptionSetupFactory
     */
    private $eavOptionSetupFactory;
    
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavOptionSetupFactory $eavOptionSetupFactory, EavSetupFactory $eavSetupFactory)
    {
        $this->eavOptionSetupFactory = $eavOptionSetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }
    
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        /** @var EavOptionSetup $optionSetup */
        $optionSetup = $this->eavOptionSetupFactory->create(['setup' => $setup]);

        $attributeCode = 'test_options';
        
        $eavSetup->addAttribute(Product::ENTITY, $attributeCode, [
            'label' => 'Test Options',
            'required' => 0,
            'is_configurable' => 0,
            'input' => 'multiselect',
            'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class
        ]);
        
        $optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, [0 => 'Foo']);
        $optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, [0 => 'Bar']);
        $optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, [0 => 'Foo']);
    }
}
```

Compatibility
-------------
- Magento 2 version 0.74.0-beta13 and maybe younger ones, too.

Support
-------
Pease use the github issue tracker.

Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
Vinai Kopp  
[http://www.netzarbeiter.com](http://www.netzarbeiter.com)  
[@VinaiKopp](https://twitter.com/VinaiKopp)

Copyright
---------
(c) 2015 Vinai Kopp
