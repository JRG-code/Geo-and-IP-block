/**
 * Admin scripts for Geo & IP Blocker
 *
 * @package GeoIPBlocker
 */

(function ($) {
	'use strict';

	/**
	 * Settings Page Handler
	 */
	const SettingsPage = {
		init: function () {
			this.initSelect2();
			this.initBlockActionToggle();
			this.initApiProviderToggle();
			this.initLocalDatabaseToggle();
			this.initFormSubmit();
			this.initTestApiButton();
			this.initUpdateDatabaseButton();
			this.initCountryActions();
			this.initCountryTags();
		},

		/**
		 * Initialize Select2 for country selection
		 */
		initSelect2: function () {
			if (typeof $.fn.select2 !== 'undefined') {
				$('.geo-ip-blocker-country-select').select2({
					placeholder: geoIPBlockerSettings.strings.selectCountries || 'Select countries...',
					allowClear: true,
					width: '100%'
				});

				// Update selected countries list when selection changes
				$('.geo-ip-blocker-country-select').on('change', function () {
					SettingsPage.updateSelectedCountriesList();
				});
			}
		},

		/**
		 * Toggle block action fields visibility
		 */
		initBlockActionToggle: function () {
			const $blockAction = $('#block_action');

			if ($blockAction.length) {
				const toggleFields = function () {
					const action = $blockAction.val();

					// Hide all action-specific fields
					$('.block-action-option').hide();

					// Show relevant fields
					if (action === 'message') {
						$('.block-action-message').show();
					} else if (action === 'redirect') {
						$('.block-action-redirect').show();
					} else if (action === 'page') {
						$('.block-action-page').show();
					}
				};

				// Initial toggle
				toggleFields();

				// Toggle on change
				$blockAction.on('change', toggleFields);
			}
		},

		/**
		 * Toggle API provider fields visibility
		 */
		initApiProviderToggle: function () {
			const $provider = $('#geolocation_provider');

			if ($provider.length) {
				const toggleFields = function () {
					const provider = $provider.val();

					// Hide all provider-specific fields
					$('.api-provider-option').hide();

					// Show relevant fields
					if (provider === 'maxmind') {
						$('.api-provider-maxmind').show();

						// Check if local database is enabled
						if ($('#enable_local_database').is(':checked')) {
							$('.local-db-option').show();
						}
					} else if (provider === 'ip2location') {
						$('.api-provider-ip2location').show();
					}
				};

				// Initial toggle
				toggleFields();

				// Toggle on change
				$provider.on('change', toggleFields);
			}
		},

		/**
		 * Toggle local database options
		 */
		initLocalDatabaseToggle: function () {
			const $enableLocalDb = $('#enable_local_database');

			if ($enableLocalDb.length) {
				const toggleFields = function () {
					if ($enableLocalDb.is(':checked')) {
						$('.local-db-option').show();
					} else {
						$('.local-db-option').hide();
					}
				};

				// Initial toggle
				toggleFields();

				// Toggle on change
				$enableLocalDb.on('change', toggleFields);
			}
		},

		/**
		 * Handle AJAX form submission
		 */
		initFormSubmit: function () {
			$('#geo-ip-blocker-settings-form').on('submit', function (e) {
				e.preventDefault();

				const $form = $(this);
				const $button = $form.find('input[type="submit"]');
				const $spinner = $form.find('.spinner');
				const $message = $form.find('.geo-ip-blocker-message');

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$message.html('');

				// Prepare form data
				const formData = new FormData($form[0]);
				formData.append('action', 'geo_ip_blocker_save_settings');
				formData.append('nonce', geoIPBlockerSettings.nonce);

				// Convert FormData to object for AJAX
				const data = {};
				formData.forEach((value, key) => {
					// Handle arrays (like country selections)
					if (key.includes('[]')) {
						const arrayKey = key.replace('[]', '');
						if (!data[arrayKey]) {
							data[arrayKey] = [];
						}
						data[arrayKey].push(value);
					} else {
						data[key] = value;
					}
				});

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: data,
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$message.html('<span class="success">' + (response.data.message || geoIPBlockerSettings.strings.saved) + '</span>');

							// Clear message after 3 seconds
							setTimeout(function () {
								$message.html('');
							}, 3000);
						} else {
							$message.html('<span class="error">' + (response.data.message || geoIPBlockerSettings.strings.error) + '</span>');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$message.html('<span class="error">' + geoIPBlockerSettings.strings.error + '</span>');
					}
				});
			});
		},

		/**
		 * Handle test API connection button
		 */
		initTestApiButton: function () {
			$('#test-api-connection').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.next('.spinner');
				const $result = $('.api-test-result');
				const provider = $('#geolocation_provider').val();
				let apiKey = '';

				if (provider === 'maxmind') {
					apiKey = $('#maxmind_license_key').val();
				} else if (provider === 'ip2location') {
					apiKey = $('#ip2location_api_key').val();
				}

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$result.html('');

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_test_api',
						nonce: geoIPBlockerSettings.nonce,
						provider: provider,
						api_key: apiKey
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$result.html('<span class="success">' + response.data.message + '</span>');
						} else {
							$result.html('<span class="error">' + (response.data.message || geoIPBlockerSettings.strings.testFailed) + '</span>');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$result.html('<span class="error">' + geoIPBlockerSettings.strings.testFailed + '</span>');
					}
				});
			});
		},

		/**
		 * Handle update database button
		 */
		initUpdateDatabaseButton: function () {
			$('#update-database').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.next('.spinner');
				const $result = $('.database-update-result');

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$result.html('');

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_update_database',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$result.html('<span class="success">' + (response.data.message || geoIPBlockerSettings.strings.updateSuccess) + '</span>');

							// Reload page after 2 seconds to show updated time
							setTimeout(function () {
								location.reload();
							}, 2000);
						} else {
							$result.html('<span class="error">' + (response.data.message || geoIPBlockerSettings.strings.updateFailed) + '</span>');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$result.html('<span class="error">' + geoIPBlockerSettings.strings.updateFailed + '</span>');
					}
				});
			});
		},

		/**
		 * Handle country selection actions (Select All / Clear All)
		 */
		initCountryActions: function () {
			// Select All
			$('#select-all-countries').on('click', function (e) {
				e.preventDefault();

				const $select = $('.geo-ip-blocker-country-select');
				$select.find('option').prop('selected', true);
				$select.trigger('change');
			});

			// Clear All
			$('#clear-all-countries').on('click', function (e) {
				e.preventDefault();

				if (confirm(geoIPBlockerSettings.strings.confirmClear)) {
					const $select = $('.geo-ip-blocker-country-select');
					$select.val(null).trigger('change');
				}
			});
		},

		/**
		 * Update the selected countries visual list
		 */
		updateSelectedCountriesList: function () {
			const $select = $('.geo-ip-blocker-country-select');
			const $list = $('#selected-countries-list');
			const selected = $select.val() || [];

			if (selected.length === 0) {
				$list.html('<p class="no-countries">' + (geoIPBlockerSettings.strings.noCountries || 'No countries selected.') + '</p>');
				return;
			}

			let html = '';
			selected.forEach(function (code) {
				const name = $select.find('option[value="' + code + '"]').text();
				html += '<span class="country-tag" data-country="' + code + '">' +
					name +
					'<button type="button" class="remove-country" aria-label="Remove">&times;</button>' +
					'</span>';
			});

			$list.html(html);
		},

		/**
		 * Handle removing countries from tags
		 */
		initCountryTags: function () {
			$(document).on('click', '.remove-country', function (e) {
				e.preventDefault();

				const $tag = $(this).closest('.country-tag');
				const countryCode = $tag.data('country');
				const $select = $('.geo-ip-blocker-country-select');

				// Remove from Select2
				const currentValues = $select.val() || [];
				const newValues = currentValues.filter(function (value) {
					return value !== countryCode;
				});

				$select.val(newValues).trigger('change');
			});
		}
	};

	/**
	 * Initialize when DOM is ready
	 */
	$(document).ready(function () {
		// Initialize settings page if on settings page
		if ($('.geo-ip-blocker-settings').length) {
			SettingsPage.init();
		}
	});

})(jQuery);
