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
			formProgressBarPlaceholder: '#import-progress-bar',
			runningImportsWrapper: '#running-imports-wrapper',
			progressBarWrapper: '.progress-bar-wrapper'
		};
		// Array with import IDs where progress was generated
		this.progressBarExistForImport = [];
	}

	ImportModule.prototype = {
		/**
		 * Init main method
		 */
		init() {
			this.submitButtonInit();
			this.loadRunningImports();
		},

		/**
		 * Init info about running imports
		 */
		loadRunningImports() {
			$.ajax({
				type: 'GET',
				url: TYPO3.settings.ajaxUrls['pxapmimporter-all-imports']
			}).done(response => {
				let wrapper = this.getjQueryInstance('runningImportsWrapper');

				for (let i = 0; i < response.length; i++) {
					let runningImport = response[i];
					if (runningImport.status !== true || this.progressBarExistForImport.indexOf(runningImport.import) !== -1) {
						continue;
					}

					this.progressBarExistForImport.push(runningImport.import);
					let template = wrapper.find('#running-import-template').clone();
					template.attr('id', '');
					template.find('.running-import-name').text(runningImport.name);
					template.find('.running-import-start').text(this.timestampToHumanDate(runningImport.start));

					let progressBarTemplate = this.initProgressBar(runningImport.import, runningImport.progress);

					template.find('.import-progress-bar').html(progressBarTemplate);
					wrapper.append(template);
					wrapper.removeClass('hidden');
					progressBarTemplate.closest(this.selectors.progressBarWrapper).removeClass('hidden');
				}
			});
		},

		/**
		 * Submit state for import button
		 */
		submitButtonInit() {
			this.getjQueryInstance('importSubmit').on('click', e => {
				let $button = $(e.currentTarget);
				$button.button('loading');

				let parentWrapper = $button.closest(this.selectors['parentWrapper']);
				parentWrapper.addClass('loading');

				setTimeout(() => {
					this.loadRunningImports();
				}, 1000);
			});
		},

		/**
		 * Init progress bar
		 *
		 * @param importId
		 * @param currentProgress
		 */
		initProgressBar(importId, currentProgress) {
			let progressBarTemplate = $(this.getProgressBar());
			let progressBar = progressBarTemplate.find('.progress-bar');

			currentProgress = currentProgress || 0;

			progressBar
				.css('width', currentProgress + '%')
				.text(currentProgress + '%');

			let importProgress = setInterval(function () {
				$.ajax({
					type: 'POST',
					url: TYPO3.settings.ajaxUrls['pxapmimporter-progress-bar'],
					data: {
						importId: importId,
					}
				}).done(response => {
					let progress = response.progress;

					if (progress > 100 || response.status === false) {
						progress = 100;
					}

					progressBar
						.css('width', progress + '%')
						.text(progress + '%');

					if (progress >= 100) {
						clearInterval(importProgress);
					}
				});
			}, 1000);

			return progressBar;
		},

		/**
		 * Selector for element
		 *
		 * @param element
		 * @return string
		 */
		getjQueryInstance(element) {
			return $(this.selectors[element] || '');
		},

		/**
		 * Progress bar template
		 *
		 * @return {string}
		 */
		getProgressBar() {
			return '<div class="progress">\n' +
				'    <div class="progress-bar progress-bar-striped active" role="progressbar"\n' +
				'         aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">\n' +
				'    </div>\n' +
				'</div>';
		},

		/**
		 * Get formatted dated
		 * @param timestamp
		 * @return {string}
		 */
		timestampToHumanDate(timestamp) {
			let date = new Date(timestamp * 1000);

			let formattedDate = '';
			formattedDate += this.prependDateWithZero(date.getDate());
			formattedDate += '.' + this.prependDateWithZero(date.getMonth());
			formattedDate += '.' + date.getFullYear();
			formattedDate += ', ' + this.prependDateWithZero(date.getHours()) + ':' + this.prependDateWithZero(date.getMinutes()) + ':' + this.prependDateWithZero(date.getSeconds());

			return formattedDate;
		},

		/**
		 * Prepend zero for date if needed
		 * @param value
		 * @return {string}
		 */
		prependDateWithZero(value) {
			return value < 10
				? ('0' + value.toString())
				: value.toString();
		}
	};

	return ImportModule;
});