#
# Table structure for table 'tx_pxaproductmanager_domain_model_product'
#
CREATE TABLE tx_pxaproductmanager_domain_model_product (
	pm_importer_import_id_hash varchar(55) DEFAULT '' NOT NULL,
	pm_importer_import_id varchar(255) DEFAULT '' NOT NULL,

	KEY importhash (pm_importer_import_id_hash ,sys_language_uid, pid)
);

#
# Table structure for table 'sys_category'
#
CREATE TABLE sys_category (
	pm_importer_import_id_hash varchar(55) DEFAULT '' NOT NULL,
	pm_importer_import_id varchar(255) DEFAULT '' NOT NULL,

	KEY importhash (pm_importer_import_id_hash ,sys_language_uid, pid)
);

#
# Table structure for table 'tx_pxaproductmanager_domain_model_attribute'
#
CREATE TABLE tx_pxaproductmanager_domain_model_attribute (
	pm_importer_import_id_hash varchar(55) DEFAULT '' NOT NULL,
	pm_importer_import_id varchar(255) DEFAULT '' NOT NULL,

	KEY importhash (pm_importer_import_id_hash ,sys_language_uid, pid)
);

#
# Table structure for table 'tx_pxaproductmanager_domain_model_option'
#
CREATE TABLE tx_pxaproductmanager_domain_model_option (
	pm_importer_import_id_hash varchar(55) DEFAULT '' NOT NULL,
	pm_importer_import_id varchar(255) DEFAULT '' NOT NULL,

	KEY importhash (pm_importer_import_id_hash ,sys_language_uid, pid)
);

#
# Table structure for table 'tx_pxaproductmanager_domain_model_attributeset'
#
CREATE TABLE tx_pxaproductmanager_domain_model_attributeset (
	pm_importer_import_id_hash varchar(55) DEFAULT '' NOT NULL,
	pm_importer_import_id varchar(255) DEFAULT '' NOT NULL,

	KEY importhash (pm_importer_import_id_hash ,sys_language_uid, pid)
);

#
# Table structure for table 'tx_pxapmimporter_domain_model_progress'
#
CREATE TABLE tx_pxapmimporter_domain_model_progress (

    configuration varchar(255) DEFAULT '' NOT NULL,
    progress double(11,2) DEFAULT '0.00' NOT NULL

);