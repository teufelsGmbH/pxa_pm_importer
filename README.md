# Product Manager Importer
[Product manager](https://github.com/pixelant/pxa_product_manager)

## Short information

This extension was for create to make it easier to import products and products data from **different sources**.

By default it supports import from **CSV and Excel** files.

Import configuration should be provided by **Yaml**.

## Example configuration and source files

You can find some examples of import configuration and source data structure checking next folders:

Yaml configuration files `EXT:pxa_pm_importer/Configuration/Example/Yaml/`

Source data files `EXT:pxa_pm_importer/Resources/Private/ExampleData/`

## Usage

### How to run import configuration

First you need to create import configuration:

- All import configuration reocrds should exist only with PID - 0.
- Go to `List` module on root page with **UID 0**.
- Create new record "PM Import". IT has next options
    - *Name* - anything.
    - *Use local file* - check this, if import is not using any additional import extension and import configuration file is located in fileadmin. If your import is more complex leave this unchecked. 
    - *Configuration file provided by extensions importers* - list of configuration files registered by other import extensions.
    - *Local file* - fileadmin configuration file.

#### Backend module
After import configuration is created you can run it from **Backend module "PM importer"**.

#### Scheduler
To run import from **scheduler**, create new `Extbase CommandController Task (extbase)`, there should choose `PxaPmImporter Import: import`.
Task accept next parameters:
- *importUid: Import configuration uid* - UID of import configuration created early
- *email: Notify about import errors* - notify provided email about import errors
- *senderEmail: Sender email* - sender email in case notification email was set

### Simple import

If you want to import data from CSV or Excel files and data is not really complex, possibilities of extension should be enough.

But in case you need to do more complex import, or source of data is an API or anything else, read about to [extend importer](#advanced-import)

#### Import yaml configuration

You can create new Yaml configuration file with you import configuration somewhere in "fileadmin".

Example:
```yaml
source:
  SourceClass:
    # Different source settings
importers:
  ImporterClassName:
    # Layer between raw data and importer
    adapter:
      className: 'AdapterDataClass'
      mapping:
        # Import unique identifier for all fields
        id: 'A'
        # Per language
        languages:
          0:
            # Field name to column number from raw data, 0 is first
            # or field name to column letter like in excel
            title: 1
            parent: 2
          1:
            # Or use column letter
            title: 'C',
            parent: 'D'
    # Identifier field from data adapter
    identifierField: 'id'
    # Import storage
    pid: 136

    # Mapping fields, data adapter should return array with associative array
    mapping:
      title:
        # Property name is necessary only if it differ from field name
        property: 'title'
        processor: 'Pixelant\PxaPmImporter\Processors\StringProcessor'
        # Custom settings
        validation: 'required'
      parent:
        processor: 'Pixelant\PxaPmImporter\Processors\Relation\CategoryProcessor'
```

##### Configuration parts
###### Source

Source is responsible for reading data from different sources. Currently extension support two import sources:
- `Pixelant\PxaPmImporter\Service\Source\CsvSource`
- `Pixelant\PxaPmImporter\Service\Source\ExcelSource`

*CsvSource* supports next options:
```yaml
skipRows: 1 # Skip number of top rows
delimiter: , # CSV Delimiter
filePath: 'file.csv' #path to file
```
*ExcelSource* supports next options:
```yaml
skipRows: 1 # Skip number of top rows
sheet: -1 # Sheet number, starts from 0, -1 - active sheet
filePath: 'file.csv' #path to file
```
###### Importers
Importer do actual import.

Available importers:
- `Pixelant\PxaPmImporter\Service\Importer\AttributesImporter` - import product attributes
- `Pixelant\PxaPmImporter\Service\Importer\CategoriesImporter` - import categories
- `Pixelant\PxaPmImporter\Service\Importer\ProductsImporter` - import products

**Import adapter**

Every importer require **adapter**. Adapter should transform raw data from source to associative array.

Also adapter need to prepare data for language layers is there are such.

By default extension has `Pixelant\PxaPmImporter\Adapter\DefaultDataAdapter`

**Adapter require mapping configuration**:
- *id* - tells in which column unique identifier is
- *mapping* - language UID to field mapping array, where **field name => to column number or letter**

**Importer configuration**

- *identifierField* - field name with unique identifier from data adapter
- *pid* - Storage
- *mapping* - Field to extbase property model mapping rules. Support next settings:
    - *property* - Extbase property name. Set this in case property name differs from field name
    - *processor* - Custom field processor. If set processor take care of setting model property value, otherwise value will be set as simple string without any processing.
    - *validation* - Only required is supported so far. But you can implement any in your own processor
    - Any other options will be passed as configuration array to processor

##### Processors

Processor purpose is to transform data(field value) in a way that it can be set to model property.
Processors are responsible for setting value to property.

Extension has next processors out of box:

- `Pixelant\PxaPmImporter\Processors\BooleanProcessor`- boolean values. No parameters.
- `Pixelant\PxaPmImporter\Processors\FloatProcessor` - float values. No parameters.
- `Pixelant\PxaPmImporter\Processors\IntegerProcessor` - integer values. No parameter
- `Pixelant\PxaPmImporter\Processors\StringProcessor` - string values. No parameters.
- `Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor` - set value for product attribute. Parameters:
    - *attributeUid* - UID of attribute
    - *dateFormat* - optional, date format for *\DateTime::createFromFormat*. Parse date for date attribute type.
- `Pixelant\PxaPmImporter\Processors\Relation\AttributeOptionsProcessor` - transform field value (for ex.'Red,Blue,Green') into attribute options and attach to attribute. Use only for attributes importer. Parameters:
    - *treatIdentifierAsUid* - if values are comma-separated uids of options instead of titles.
- `Pixelant\PxaPmImporter\Processors\Relation\CategoryProcessor` - transform field value like 'Food,Car' into categories and attach to object. Use for products importer. Parameters:
    - *treatIdentifierAsUid* - if values are comma-separated uids of categories.
- `Pixelant\PxaPmImporter\Processors\Relation\RelatedProductsProcessor` - transform product identifiers into products and attach to object. Use for products importer. Parameters:
    - *treatIdentifierAsUid* - if values are comma-separated uids of products.
- `Pixelant\PxaPmImporter\Processors\Relation\Files\LocalFileProcessor` - attach file by name. Parameters:
    - *storageUid* - optional storage UID, 1 - default.
    - *folder* - relative folder path where file can be found. Skip "fileadmin" for default storage.

## Advanced import  

If your import is more advanced and require different sources, or custom data adapter, or custom processors you can do it with your own importer extension.

Create new extension, for example "pm_myimport".

In `ext_localconf.php` add next code:

```php
if (TYPO3_MODE === 'BE') {
    // Register importer
    \Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pm_myimport');
}
```

This will register your extension as import configration provider.

Import configuration files put in:

`EXT:pxa_pm_importer/Configuration/Yaml`

As second parameter you can provide array of custom paths where to fetch yamk files, but these should be inside `Configuration` folder.

Then in your import configuration file you can use **your own importers, source providers, data adapter and processors**.

**Important** that your custom classes implements required interface.

- Adapter implement `Pixelant\PxaPmImporter\Adapter\AdapterInterface`
- Processor implement `Pixelant\PxaPmImporter\Processors\FieldProcessorInterface`
    - `Pixelant\PxaPmImporter\Processors\Relation\AbstractRelationFieldProcessor` is useful to handle relation like 1:1, 1:n and n:m
    - `Pixelant\PxaPmImporter\Processors\AbstractFieldProcessor` basic class to work with simple values, like string and numbers 
- Source implement `Pixelant\PxaPmImporter\Service\Source\SourceInterface`
- Importer implement `Pixelant\PxaPmImporter\Service\Importer\ImporterInterface`
    - `Pixelant\PxaPmImporter\Service\Importer\AbstractImporter` - basic class for products, categories and attributes import

 