# Product Manager Importer
[Product manager](https://github.com/pixelant/pxa_product_manager)

## Short information

This extension was for created to make it easier to import products and products data from **different sources**.

By default it supports import from **CSV and Excel** files.

!!! If you want to use Excel source additionally install composer package `composer req phpoffice/phpspreadsheet`

Import configuration is provided by **Yaml**.

## Example configuration and source files

You can find some examples of import configuration and source data structure in next folders:

- Yaml configuration files `EXT:pxa_pm_importer/Configuration/Example/Yaml/`
- Source data files `EXT:pxa_pm_importer/Resources/Private/ExampleData/`

## Usage

### How to create import configuration

Create new extension, for example "pm_myimport".

In `ext_tables.php` add next code:

```php
// Register importer
\Pixelant\PxaPmImporter\Utility\ImportersRegistry::registerImporter('pm_myimport');
```

This will register your extension as import configuration provider.

By default extension will look for configuration files in:

`EXT:pm_myimport/Configuration/Yaml`

But you can add as second parameter array of custom paths where to fetch YAML files, but these should be under `Configuration` folder.

Then in your import configuration file you can use **your own importers, source providers, data adapter and processors**.

#### Log
Every import execution is saved in separate log file.
By default all log files are saved in `typo3temp/var/logs`, file prefix is `pm_importer_YYYY_mm_dd_hours:minutes:seconds.log`
You can change log path in import configuration


#### Backend module
After import configuration is created you can run it from **Backend module "PM importer"**.

#### Scheduler
In order to run import from **scheduler**, create new `Execute console commands` task. From available commands choose **pxapmimporter:import: Import "pxa_product_manager" extension related records.**
Task accept next parameters:
- *configurations:* - Comma list of path to configuration files to run. For ex. "EXT:pxa_pm_importer/Configuration/Example/Yaml/AttributesCsvSample.yaml"
- *adminEmails: Notify about import errors* - Notify provided emails about import errors
- *senderEmail: Sender email* - Sender email in case notification email was set

### Simple import

If you want to import data from CSV or Excel files and data is not really complex, possibilities of extension should be enough.

In case you want just run some custom configuration, but don't want to create extension for this, you can do it from command line.

```bash
./vendor/bin/typo3cms pxapmimporter:import PATH_TO_CONFIGURATION_FILE
```

#### Import yaml configuration

You can create new Yaml configuration file with you import configuration somewhere in "fileadmin".

Example:
```yaml
log:
  # Custom log path
  path: 'fileadmin/import/log/product_import.log' 
sources:
  SourceClass:
    # Different source settings
importers:
  ImporterName:
    # Override default importer class
    #importer: Class_Name
    # Unique identifier field provided by Adapter
    identifierField: 'id'

    # Domain model name
    domainModel: Pixelant\PxaProductManager\Domain\Model\Attribute

    # Allowed import operations
    # default is 'create,update,localize,createLocalize'
    allowedOperations: 'create,update'

    # Settings for new records
    importNewRecords:
      # Storage of new records
      pid: 22

    # Storage of records. Import will check storage for records
    storage:
      # Comma-separated list of folders
      pid: 22
      # Recursive level
      recursive: 0

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
```

##### Configuration parts

###### Log

In log settings it's possible to set custom path where to write file log.

###### Source

Source is responsible for reading data from different sources. Currently extension support two import sources:

!!! **It's possible to have multiple source**. Script will run imports for each source.
 
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

**Use multiple columns to gerenate identifier**:

Following configuration would set identifier to '1100101023se1' if column ITEMID is '1100101023' and DATAAREAID is 'se1', useful when no sigle field in source can be used as a unique identifier.

```yaml
# Adapter settings
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

**Importer configuration**
```yaml
# Override default importer class
#importer: Class_Name
# Field name with unique identifier from data adapter
identifierField: 'id'

# Domain model name
# Target import model
domainModel: Pixelant\PxaProductManager\Domain\Model\Attribute

# Allowed import operations. UPDATE is allowed by default
# default is 'create,update,localize,createLocalize'
# 'create' - Allow to create new records
# 'localize' - Allow to localize records
# 'createLocalize' - Allow to create localize records without default language record
allowedOperations: 'create'

# Settings for new records
importNewRecords:
  # Storage of new records
  pid: 22

# Storage of records. Import will check storage for records
storage:
  # Comma-separated list of folders
  pid: 22
  # Recursive level
  recursive: 0

# Mapping fields, data adapter should return array with associative array
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


##### Processors

Processor purpose is to transform data(field value) in a way that it can be set to model property.
Processors are responsible for setting value to property.

Extension has next processors out of box:

###### Simple properties processors:

- `Pixelant\PxaPmImporter\Processors\BooleanProcessor`- boolean values. No parameters.
- `Pixelant\PxaPmImporter\Processors\FloatProcessor` - float values. No parameters.
- `Pixelant\PxaPmImporter\Processors\IntegerProcessor` - integer values. No parameter
- `Pixelant\PxaPmImporter\Processors\StringProcessor` - string values. No parameters.
- `Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor` - set value for product attribute. 
- `Pixelant\PxaPmImporter\Processors\SlugProcessor` - update slug field for urls.  
- `Pixelant\PxaPmImporter\Processors\DateTimeProcessor` - DateTime values.

######Configuration example:

- Simple
```yaml
# Field to Extbase property model mapping rules. Support next settings:
title:
  # Extbase property name. Set this in case property name differs from field name
  property: 'title'
  # Custom field processor. If set processor take care of setting model property value, otherwise value will be set as simple string without any processing.
  processor: 'Pixelant\PxaPmImporter\Processors\StringProcessor'
  # Validators:
  validation:
    - required
```
- DateTimeProcessor

```yaml
date:
  processor: 'Pixelant\PxaPmImporter\Processors\DateTimeProcessor'
  # Input format, the format to use when creating a DateTime object from value (DateTime::createFromFormat)
  inputFormat: 'd/m/Y h:i:s'
  # Output format, the format to store data in entity
  outputFormat: 'U'
```

- SlugProcessor
```yaml
slug:
  processor: 'Pixelant\PxaPmImporter\Processors\SlugProcessor'
  # If DB field name doesn't match property name you need to provide DB field name in order to be able to update record
  fieldName: 'pxapm_slug'
  # Set to true in case your import source already has slug value for import and generation is not needed.
  # Otherwise(by default false) script will generate value using TCA configuration
  useImportValue: true
```

- ProductAttributeProcessor:
```yaml
color:
  processor: 'Pixelant\PxaPmImporter\Processors\ProductAttributeProcessor'
  # UID of attribute or import ID
  attributeUid: 11
  # You can set this to true if "attributeUid" above has import identifier value.
  # This means that attribute was previously imported and you want to find it by this import identifier
  treatAttributeUidAsImportUid: false
  # Optional, date format for \DateTime::createFromFormat. Parse date for date attribute type.
  dateFormat: 'Y-m-d'
```

###### Relations(1:1, 1:n, n:m) processors:

- `Pixelant\PxaPmImporter\Processors\Relation\AttributeOptionsProcessor` - transform field value (for ex.'Red,Blue,Green') into attribute options and attach to attribute. **Use only for attributes importer**.
- `Pixelant\PxaPmImporter\Processors\Relation\CategoryProcessor` - transform field value like 'Food,Car' into categories and attach to object.
- `Pixelant\PxaPmImporter\Processors\Relation\RelatedProductsProcessor` - transform product identifiers into products and attach to object.

######Configuration example:

```yaml
relatedProducts:
  processor: 'Pixelant\PxaPmImporter\Processors\Relation\RelatedProductsProcessor'
  # If values are comma-separated UIDs from DB instead of import IDs
  treatIdentifierAsUid: 1
subProducts:
  processor: 'Pixelant\PxaPmImporter\Processors\Relation\RelatedProductsProcessor'
  # If one of the related products was not found and you want to ignore this error
  disableExceptionOnFailInitEntity: true
```

###### Files processor

- `Pixelant\PxaPmImporter\Processors\Relation\Files\LocalFileProcessor` - attach file by name.

######Configuration example:

```yaml
images:
  processor: 'Pixelant\PxaPmImporter\Processors\Relation\Files\LocalFileProcessor'
  # Relative folder path where file can be found. Skip "fileadmin" for default storage.
  folder: 'import_files'
  # Optional storage UID, 1 - default.
  storageUid: 1
```

##### Processor validation
Every processor may have many validators.
Custom validators should implement instance of `\Pixelant\PxaPmImporter\Domain\Validation\Validator\ProcessorFieldValueValidatorInterface`.
See `RequiredValidator` for example on how to validate value and `ProcessorFieldValueValidatorInterface` for available validation statuses.

```yaml
validation:
 # Will use \Pixelant\PxaPmImporter\Domain\Validation\Validator\RequiredValidator
 - required
 # Or provide custom validator
 - Pixelant\MyExtension\Domain\Validation\Validator\CustomValidator
```

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

# Developers useful info

During import process Singleton `\Pixelant\PxaPmImporter\Context\ImportContext` is available with related to import data. See class for more details.

