EavOptionSetup (Magento2)  
=======================

This Magento2 extension contains a class to easily add EAV attribute options only if the specified attribute does not
already have an option with the same admin scope label.

Facts
-----
- [extension on GitHub](https://github.com/Vinai/VinaiKopp_EavOptionSetup)

Description
-----------
The EavOptionSetup class is intended to be used within setup scripts, but could actually also be used in other
situation, for example PIM system integrations.

Usage: Require EavOptionSetup using DI. Only two public methods are provided:

```php
$optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, 'Default Option Label');
$optionSetup->addAttributeOptionIfNotExistsWithStoreLabels(
    Product::ENTITY,
    $attributeCode,
    'Default Option Label',
    [1 => 'Store A Label', 3 => 'Store B Label']
);
```

The array of store scope labels is a store ID to Store Label map. The store scope label is optional, that is, not all
stores are required to have their own labels.

Usage Example
-------------

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

class InstallData implements InstallDataInterface
{
    /**
     * @var EavOptionSetup
     */
    private $eavOptionSetup;
    
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavOptionSetup $eavOptionSetup, EavSetupFactory $eavSetupFactory)
    {
        $this->eavOptionSetup = $eavOptionSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }
    
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // Add a new attribute
        $attributeCode = 'test_options';
        $eavSetup->addAttribute(Product::ENTITY, $attributeCode, [
            'label' => 'Test Options',
            'required' => 0,
            'is_configurable' => 0,
            'input' => 'multiselect',
            'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class
        ]);
        
        // Add some options
        $this->eavOptionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, 'Foo');
        $this->eavOptionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, 'Bar');
        
        // This option won't be added because there already is a `Foo` option.
        $this->optionSetup->addAttributeOptionIfNotExists(Product::ENTITY, $attributeCode, 'Foo');
    }
}
```

Compatibility
-------------
- Magento 2 version 0.74.0-beta14 and hopefully newer releases, too.

Support
-------
Please use the github issue tracker.

Contribution
------------
Any contributions are highly appreciated. The best way to contribute code is to open a
[pull request on GitHub](https://help.github.com/articles/using-pull-requests).

License
-------
BSD-3-Clause

Developer
---------
Vinai Kopp  
[http://vinaikopp.com](http://vinaikopp.com)  
[@VinaiKopp](https://twitter.com/VinaiKopp)

Copyright
---------
(c) 2015 Vinai Kopp
