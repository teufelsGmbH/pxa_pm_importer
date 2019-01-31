define(['jquery'], function ($) {
	/**
	 * Import module
	 *
	 * @constructor
	 */
	function ImportModule() {
		this.selectors = {
			importSubmit: '#pxa-pm-import-submit',
			parentWrapper: '.pxa-pm-importer',
			selectImportBox: '#pxa-import-select',
			progressBarPlaceholder: '#import-progress-bar'
		}
	}

	ImportModule.prototype = {
		/**
		 * Init main method
		 */
		init: function () {
			this.submitButtonInit();
		},

		/**
		 * Submit state for import button
		 */
		submitButtonInit: function () {
			this.getjQueryInstance('importSubmit').on('click', e => {
				let $button = $(e.currentTarget);
				$button.button('loading');

				let parentWrapper = $button.closest(this.selectors['parentWrapper']);
				parentWrapper.addClass('loading');

				// Progress bar
				let importId = parseInt(this.getjQueryInstance('selectImportBox').val());
				this.initProgressBar(importId);
			});
		},

		/**
		 * Init progress bar
		 *
		 * @param importId
		 */
		initProgressBar: function (importId) {
			let progressBarTemplate = $(this.getProgressBar());
			let progressBar = progressBarTemplate.find('.progress-bar');
			progressBar
				.css('width', '0%')
				.text('0%');

			this.getjQueryInstance('progressBarPlaceholder').html(progressBarTemplate);

			let progress = setInterval(function () {
				$.ajax({
					type: 'POST',
					url: TYPO3.settings.ajaxUrls['pxapmimporter-progress-bar'],
					data: {
						importId: importId,
					}
				}).done(({progress}) => {
					progressBar
						.css('width', progress + '%')
						.text(progress + '%');

					if (progressBar >= 100) {
						clearInterval(progress);
					}
				});
			}, 1000);
		},

		/**
		 * Selector for element
		 *
		 * @param element
		 * @return string
		 */
		getjQueryInstance: function (element) {
			return $(this.selectors[element] || '');
		},

		/**
		 * Progress bar template
		 *
		 * @return {string}
		 */
		getProgressBar: function () {
			return '<div class="progress">\n' +
				'    <div class="progress-bar progress-bar-striped active" role="progressbar"\n' +
				'         aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">\n' +
				'    </div>\n' +
				'</div>';
		}
	};

	return ImportModule;
});