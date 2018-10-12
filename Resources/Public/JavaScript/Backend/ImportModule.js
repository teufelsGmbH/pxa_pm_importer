define(['jquery'], function ($) {
	var ImportModule = {
		selectors: {
			'importSubmit': '#pxa-pm-import-submit'
		}
	};

	/**
	 * Init main method
	 */
	ImportModule.init = function () {
		this.submitButtonInit();
	};

	/**
	 * Submit state for import button
	 */
	ImportModule.submitButtonInit = function () {
		$(this.getElementSelector('importSubmit')).on('click', function () {
			$(this).button('loading');
		})
	};

	/**
	 * Selector for element
	 *
	 * @param element
	 * @return string
	 */
	ImportModule.getElementSelector = function (element) {
		return this.selectors[element] || null;
	};

	return ImportModule;
});