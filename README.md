# Product Manager Importer
[Product manager](https://github.com/pixelant/pxa_product_manager)

## Short information

This extension was for created to make it easier to import products and products data from **different sources**.

By default it supports import from **CSV and Excel** files.

Import configuration is provided by **Yaml**.

## Example configuration and source files

You can find some examples of import configuration and source data structure in next folders:

- Yaml configuration files `EXT:pxa_pm_importer/Configuration/Example/Yaml/`
- Source data files `EXT:pxa_pm_importer/Resources/Private/ExampleData/`

## Usage

### How to run import configuration

First you need to create import configuration:

- All import configuration records should exist only with PID - 0.
- Go to `List` module on root page with **UID 0**.
- Create new record "PM Import". It has next options
    - *Name* - anything.
    - *Use local file* - check this, if import configuration file is located in fileadmin. If your import is more complex and you want to use configuration from other importer this unchecked.
    - *Configuration file provided by extensions importers* - list of configuration files registered by other import extensions.
    - *Local file* - fileadmin configuration file.

#### Log
Every import execution is saved in separate log file.
By default all log files are saved in `typo3temp/var/logs`, file prefix is `pm_importer_YYYY_mm_dd_hours:minutes:seconds.log`
You can change log folder and file prefix in extension manager settings.


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

But in case you need to do more complex import, or source of data is an API or anything else, read about how to [extend importer](#advanced-import)

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
      # Any additional settings
      settings:
        dummy: true
      mapping:
        # Import unique identifier for all fields
        id: 0
        excelColumns: false
        # Per language
        languages:
          0:
            # Field name to column number from raw data, 0 is first
            title: 1
            parent: 2
          1:
            title: 3
            parent: 4
    # Identifier field from data adapter
    identifierField: 'id'
    # Import storage
    pid: 136
    
    # Shall we create an independent language layer record if parent record doesn't exist
    allowCreateLocalizationIfDefaultNotFound: false
    
    # Mapping fields, data adapter should return array with associative array
    mapping:
      title:
        # Property name is necessary only if it differ from field name
        property: 'title'
        processor: 'Pixelant\PxaPmImporter\Processors\StringProcessor'
        # Custom settings
        validation:
            - required
      parent:
        processor: 'Pixelant\PxaPmImporter\Processors\Relation\CategoryProcessor'
    # Additional settings
    settings:
      dummy: 'test'
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

Also adapter need to prepare data for language layers if there are such.

By default extension has `Pixelant\PxaPmImporter\Adapter\DefaultDataAdapter`

**Adapter require mapping configuration**:
```yaml
# Adapter settings
settings:
  dummy: 123
mapping:
  # tells in which column unique identifier is
  id: 'A'
  # Each language UID has field mapping array, where field name => to column number or letter
  # Set this if excel columns instead of numbers is used
  excelColumns: true
  languages:
    0:
      # Field name to column number from raw data, 0 is first
      title: 1
    1:
      # Or field name to column letter like in excel
      title: 'D'
```
**Important** to set "excelColumns: true" if you are using excel columns letters as column instead of nubmers. **Only number or only letters can be used for one adapter configuration**.
**Importer configuration**
```yaml
# Field name with unique identifier from data adapter
identifierField: 'id'
# Import storage
pid: 136

# Shall we create an independent language layer record if parent record doesn't exist
allowCreateLocalizationIfDefaultNotFound: false

# Mapping fields, data adapter should return array with associative array
# Importer settings
settings:
  dummy: 123
mapping:
  # Field to Extbase property model mapping rules. Support next settings:
  title:
    # Extbase property name. Set this in case property name differs from field name
    property: 'title'
    # Custom field processor. If set processor take care of setting model property value, otherwise value will be set as simple string without any processing.
    processor: 'Pixelant\PxaPmImporter\Processors\StringProcessor'
    # Only required is supported so far. But you can implement more
    # Add custom class name here
    # If just a name is provided extension will try to load it from validators folder
    validation:
        - required
    # Any other options will be passed as configuration array to processor
    customSetting: true
    anotherSettingValue: 123321
```

**Use multiple columns to gerenate identifier**:

Following configuration would set identifier to '1100101023se1' if column ITEMID is '1100101023' and DATAAREAID is 'se1', useful when no sigle field in source can be used as a unique identifier.

```yaml
# Adapter settings
settings:
  dummy: 123
mapping:
  # combine these columns for record identifier
  id:
    0: 'ITEMID'
    1: 'DATAAREAID'
  languages:
    0:
      title: 'ITEMNAME'
```

**Use adapter filters to filter rows**:

Sample usage:
I have a single API source with all products I need to import, but they are one row per language and they should be stored in different PID:s. I also need to filter out rows where PROJCATEGORYID doesn't equal 'BE Online' and CAP_CustUniqueItem equals '1'.

```yaml
# Adapter settings
settings:
  dummy: 123
mapping:
  # combine these columns for record identifier
  id:
    0: 'ITEMID'
    1: 'DATAAREAID'
  languages:
    0:
      title: 'ITEMNAME'
filters:
  # only include rows when column "PROJCATEGORYID" has string value "BE Online"
  PROJCATEGORYID:
    filter: 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter'
    value: 'BE Online'
  # AND only include rows when column "DATAAREAID" has string value "SE1"
  DATAAREAID:
    filter: 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter'
    value: 'SE1'
  # AND only include rows when column "CAP_CustUniqueItem" has string value "0"
  CAP_CustUniqueItem:
    filter: 'Pixelant\PxaPmImporter\Adapter\Filters\StringEqualsFilter'
    value: '0'
```

##### Processors

Processor purpose is to transform data(field value) in a way that it can be set to model property.
Processors are responsible for setting value to property.

Extension has next processors out of box:

- `Pixelant\PxaPmImporter\Processors\BooleanProcessor`- boolean values. No parameters.
- `Pixelant\PxaPmImporter\Processors\FloatProcessor` - float values. No parameters.
- `Pixelant\PxaPmImporter\Processors\IntegerProcessor` - integer values. No parameter
- `Pixelant\PxaPmImporter\Processors\StringProcessor` - string values. No parameters.
- `Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor` - set value for product attribute. 
- `Pixelant\PxaPmImporter\Processors\SlugProcessor` - update slug field for urls.  

##### Processor validation
Every processor may have many validators.
Custom validators should implement instance of `\Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface`.
See `RequiredValidator` for example on how to validate value and `ValidationStatusInterface` for available validation statuses.

```yaml
validation:
     # Will use \Pixelant\PxaPmImporter\Domain\Validation\Validator\RequiredValidator
     - required
     # Or provide custom validator
     - Pixelant\MyExtension\Domain\Validation\Validator\CustomValidator
```

Product attribute processor parameters:
```yaml
# UID of attribute
attributeUid: 11
# You can set this to true if "attributeUid" above has import identifier value.
# This means that attibute was previously imported and you want to find it by this import identifier
treatAttributeUidAsImportUid: false
# Optional, date format for \DateTime::createFromFormat. Parse date for date attribute type.
dateFormat: 'Y-m-d'
```
- `Pixelant\PxaPmImporter\Processors\Relation\AttributeOptionsProcessor` - transform field value (for ex.'Red,Blue,Green') into attribute options and attach to attribute. Use only for attributes importer. Parameters:
```yaml
# If values are comma-separated uids of options instead of titles.
treatIdentifierAsUid: 1
```
- `Pixelant\PxaPmImporter\Processors\Relation\CategoryProcessor` - transform field value like 'Food,Car' into categories and attach to object. Use for products importer. Parameters:
```yaml
# If values are comma-separated uids of categories.
treatIdentifierAsUid: 1
```
- `Pixelant\PxaPmImporter\Processors\Relation\RelatedProductsProcessor` - transform product identifiers into products and attach to object. Use for products importer. Parameters:
```yaml
# If values are comma-separated uids of products.
treatIdentifierAsUid: 1
```
- `Pixelant\PxaPmImporter\Processors\Relation\Files\LocalFileProcessor` - attach file by name. Parameters:
```yaml
# Optional storage UID, 1 - default.
storageUid: 1
# Relative folder path where file can be found. Skip "fileadmin" for default storage.
folder: 'uploads'
```
- `Pixelant\PxaPmImporter\Processors\DateTimeProcessor` - DateTime values. Parameters:
```yaml
# Input format, the format to use when creating a DateTime object from value (DateTime::createFromFormat)
inputFormat: 'd/m/Y h:i:s'
# Output format, the format to store data in entity
outputFormat: 'U'
```
- `Pixelant\PxaPmImporter\Processors\SlugProcessor` - Update slug field value. Parameters:
```yaml
# If DB field name doesn't match property name you need to provide DB field name in order to be able to update record
fieldName: 'pxapm_slug'
# Set to true in case your import source already has slug value for import and generation is not needed.
# Otherwise(by default false) script will generate value using TCA configuration
useImportValue: true
```

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

`EXT:pm_myimport/Configuration/Yaml`

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


## Import inside YAML
If you have one general configuration, for example product importer mapping, for all your imports and you want to have it in one place, you can create a subfolder inside "Configuration/Yaml", lets call it "imports"

`EXT:pm_myimport/Configuration/Yaml/imports`

Create "general_conf.yaml" YAML file there with and put configuration there. Then in your import file you can import like below

```yaml
# imports/general_conf.yaml
imports:
    - { resource: imports/general_conf.yaml }
```