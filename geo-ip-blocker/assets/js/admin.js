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
			this.initRegionSelect();
			this.initCountryTags();
			this.initIPManagement();
			this.initExceptionsManagement();
		},

		/**
		 * Initialize Select2 for country and region selection
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

				// Initialize Select2 for region selector
				$('.geo-ip-blocker-region-select').select2({
					placeholder: 'Select a region...',
					allowClear: true,
					width: '100%'
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

				// Add unchecked checkboxes explicitly as "0"
				// Checkboxes that are not checked don't get submitted
				$form.find('input[type="checkbox"]').each(function() {
					const $checkbox = $(this);
					const name = $checkbox.attr('name');

					if (name && !$checkbox.is(':checked')) {
						formData.append(name, '0');
					}
				});

				// Convert FormData to object for AJAX
				const settings = {};
				formData.forEach((value, key) => {
					// Handle arrays (like country selections)
					if (key.includes('[]')) {
						const arrayKey = key.replace('[]', '');
						if (!settings[arrayKey]) {
							settings[arrayKey] = [];
						}
						settings[arrayKey].push(value);
					} else {
						settings[key] = value;
					}
				});

				// Prepare final data object with settings nested correctly
				const data = {
					action: 'geo_ip_blocker_save_settings',
					nonce: geoIPBlockerSettings.nonce,
					settings: settings
				};

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
		 * Handle region selection to add multiple countries at once
		 */
		initRegionSelect: function () {
			$('#region-select').on('change', function (e) {
				const $regionSelect = $(this);
				const $option = $regionSelect.find('option:selected');
				const countries = $option.data('countries');

				if (!countries) {
					return;
				}

				// Convert comma-separated string to array
				const countryArray = countries.toString().split(',');
				const $countrySelect = $('.geo-ip-blocker-country-select');

				// Get currently selected values
				let currentValues = $countrySelect.val() || [];
				if (!Array.isArray(currentValues)) {
					currentValues = [currentValues];
				}

				// Add region countries to selection (avoiding duplicates)
				countryArray.forEach(function (countryCode) {
					if (currentValues.indexOf(countryCode) === -1) {
						currentValues.push(countryCode);
					}
				});

				// Update selection
				$countrySelect.val(currentValues).trigger('change');

				// Reset region selector
				$regionSelect.val('').trigger('change');

				// Show notification
				const regionName = $option.text();
				if (typeof geoIPBlockerSettings !== 'undefined' && geoIPBlockerSettings.strings) {
					alert(regionName + ' countries added to selection.');
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
		},

		/**
		 * Initialize IP Management
		 */
		initIPManagement: function () {
			// Add IP button handler
			$(document).on('click', '[data-action="add-ip"]', function (e) {
				e.preventDefault();

				const $button = $(this);
				const listType = $button.data('list-type');
				const $input = $('#' + listType + '-ip-input');
				const ip = $input.val().trim();
				const $spinner = $button.siblings('.spinner');
				const $message = $button.siblings('.ip-message');

				if (!ip) {
					return;
				}

				// Validate IP format
				if (!SettingsPage.validateIPFormat(ip)) {
					$message.html('<span class="error">' + geoIPBlockerSettings.strings.invalidIP + '</span>');
					setTimeout(function () {
						$message.html('');
					}, 3000);
					return;
				}

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$message.html('');

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_add_ip',
						nonce: geoIPBlockerSettings.nonce,
						ip: ip,
						list_type: listType
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$message.html('<span class="success">' + (response.data.message || geoIPBlockerSettings.strings.ipAdded) + '</span>');
							$input.val('');

							// Add IP to list
							SettingsPage.addIPToList(ip, listType);

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

			// Remove IP button handler
			$(document).on('click', '.remove-ip', function (e) {
				e.preventDefault();

				if (!confirm(geoIPBlockerSettings.strings.confirmRemoveIP)) {
					return;
				}

				const $button = $(this);
				const listType = $button.data('list-type');
				const ip = $button.data('ip');

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_remove_ip',
						nonce: geoIPBlockerSettings.nonce,
						ip: ip,
						list_type: listType
					},
					success: function (response) {
						if (response.success) {
							// Remove IP from list
							$button.closest('.ip-item').fadeOut(300, function () {
								$(this).remove();
								SettingsPage.updateIPCount(listType);

								// Check if list is empty
								const $list = $('.ip-list-container[data-list-type="' + listType + '"] .ip-list');
								if ($list.find('.ip-item').length === 0) {
									$list.html('<p class="no-ips">' + (listType === 'blacklist' ? 'No IPs in blacklist.' : 'No IPs in whitelist.') + '</p>');
								}
							});
						} else {
							alert(response.data.message || geoIPBlockerSettings.strings.error);
						}
					},
					error: function () {
						alert(geoIPBlockerSettings.strings.error);
					}
				});
			});

			// Add current IP button handler
			$(document).on('click', '.add-current-ip', function (e) {
				e.preventDefault();

				const $button = $(this);
				const listType = $button.data('list-type');
				const ip = $button.data('ip');
				const $input = $('#' + listType + '-ip-input');

				$input.val(ip);
				$button.siblings('[data-action="add-ip"]').click();
			});

			// IP search functionality
			$(document).on('keyup', '.ip-search', function () {
				const searchTerm = $(this).val().toLowerCase();
				const $container = $(this).closest('.ip-list-container');
				const $items = $container.find('.ip-item');

				$items.each(function () {
					const ip = $(this).data('ip').toLowerCase();
					if (ip.indexOf(searchTerm) > -1) {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			});
		},

		/**
		 * Validate IP format (IP, CIDR, or range)
		 */
		validateIPFormat: function (ip) {
			// IPv4 pattern
			const ipv4Pattern = /^(\d{1,3}\.){3}\d{1,3}$/;
			// CIDR pattern
			const cidrPattern = /^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/;
			// Range pattern
			const rangePattern = /^(\d{1,3}\.){3}\d{1,3}-(\d{1,3}\.){3}\d{1,3}$/;
			// IPv6 pattern (simplified)
			const ipv6Pattern = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;

			return ipv4Pattern.test(ip) || cidrPattern.test(ip) || rangePattern.test(ip) || ipv6Pattern.test(ip);
		},

		/**
		 * Add IP to list visually
		 */
		addIPToList: function (ip, listType) {
			const $list = $('.ip-list-container[data-list-type="' + listType + '"] .ip-list');
			const $noIps = $list.find('.no-ips');

			if ($noIps.length) {
				$noIps.remove();
			}

			const $ipItem = $('<div class="ip-item" data-ip="' + ip + '">' +
				'<span class="ip-address">' + ip + '</span>' +
				'<button type="button" class="button button-link-delete remove-ip" data-list-type="' + listType + '" data-ip="' + ip + '">Remove</button>' +
				'</div>');

			$list.prepend($ipItem);
			$ipItem.hide().fadeIn(300);

			SettingsPage.updateIPCount(listType);
		},

		/**
		 * Update IP count
		 */
		updateIPCount: function (listType) {
			const $container = $('.ip-list-container[data-list-type="' + listType + '"]');
			const count = $container.find('.ip-item').length;
			$container.find('.ip-count').text(count + ' IPs');
		},

		/**
		 * Initialize Exceptions Management
		 */
		initExceptionsManagement: function () {
			// Initialize Select2 for users
			if (typeof $.fn.select2 !== 'undefined' && $('.geo-ip-blocker-users-select').length) {
				$('.geo-ip-blocker-users-select').select2({
					placeholder: geoIPBlockerSettings.strings.searchUsers || 'Search users...',
					allowClear: true,
					width: '100%',
					ajax: {
						url: geoIPBlockerSettings.ajaxUrl,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								q: params.term,
								action: 'geo_ip_blocker_search_users',
								nonce: geoIPBlockerSettings.nonce
							};
						},
						processResults: function (data) {
							return {
								results: data.data.results
							};
						},
						cache: true
					},
					minimumInputLength: 2
				});
			}

			// Initialize Select2 for pages
			if (typeof $.fn.select2 !== 'undefined' && $('.geo-ip-blocker-pages-select').length) {
				$('.geo-ip-blocker-pages-select').select2({
					placeholder: geoIPBlockerSettings.strings.searchPages || 'Search pages...',
					allowClear: true,
					width: '100%',
					ajax: {
						url: geoIPBlockerSettings.ajaxUrl,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								q: params.term,
								action: 'geo_ip_blocker_search_pages',
								nonce: geoIPBlockerSettings.nonce
							};
						},
						processResults: function (data) {
							return {
								results: data.data.results
							};
						},
						cache: true
					},
					minimumInputLength: 2
				});
			}
		}
	};

	/**
	 * Tools Tab Handler
	 */
	const ToolsTab = {
		init: function () {
			this.initIPLocationTest();
			this.initDatabaseActions();
			this.initSettingsImportExport();
			this.initDebugTools();
		},

		/**
		 * Initialize IP Location Test
		 */
		initIPLocationTest: function () {
			// Detect My Location button
			$('#detect-my-location-button').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $results = $('#my-location-results');

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);

				// Get current IP from button context
				const currentIP = $button.closest('td').find('strong').text().trim();

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_test_ip_location',
						nonce: geoIPBlockerSettings.nonce,
						ip: currentIP
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success && response.data.data) {
							const data = response.data.data;
							$('#my-result-country').text((data.country_name || 'N/A') + ' (' + (data.country_code || 'N/A') + ')');
							$('#my-result-region').text(data.region || 'N/A');
							$('#my-result-city').text(data.city || 'N/A');
							$('#my-result-isp').text(data.isp || 'N/A');

							// Determine blocking status (simplified - would need backend logic)
							const status = data.is_blocked ? '✗ Blocked' : '✓ Allowed';
							const statusClass = data.is_blocked ? 'error' : 'success';
							$('#my-result-status').html('<span class="' + statusClass + '">' + status + '</span>');

							if (data.block_reason) {
								$('#my-result-reason').text(data.block_reason);
								$('#my-result-reason-row').show();
							} else {
								$('#my-result-reason-row').hide();
							}

							$results.slideDown();
						} else {
							alert(response.data.message || 'Error detecting location');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						alert('Error detecting location');
					}
				});
			});

			// Test Other IP button
			$('#test-other-ip-button').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $input = $('#test-other-ip-input');
				const $spinner = $button.siblings('.spinner');
				const $results = $('#other-ip-results');
				const ip = $input.val().trim();

				if (!ip) {
					alert('Please enter an IP address');
					return;
				}

				// Show spinner
				$spinner.addClass('is-active');
				$button.prop('disabled', true);

				// Send AJAX request
				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_test_ip_location',
						nonce: geoIPBlockerSettings.nonce,
						ip: ip
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success && response.data.data) {
							const data = response.data.data;
							$('#other-result-ip').text(ip);
							$('#other-result-country').text((data.country_name || 'N/A') + ' (' + (data.country_code || 'N/A') + ')');
							$('#other-result-region').text(data.region || 'N/A');
							$('#other-result-city').text(data.city || 'N/A');
							$('#other-result-isp').text(data.isp || 'N/A');

							const status = data.is_blocked ? '✗ Blocked' : '✓ Allowed';
							const statusClass = data.is_blocked ? 'error' : 'success';
							$('#other-result-status').html('<span class="' + statusClass + '">' + status + '</span>');

							if (data.block_reason) {
								$('#other-result-reason').text(data.block_reason);
								$('#other-result-reason-row').show();
							} else {
								$('#other-result-reason-row').hide();
							}

							$results.slideDown();
						} else {
							alert(response.data.message || 'Error testing IP');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						alert('Error testing IP');
					}
				});
			});
		},

		/**
		 * Initialize Database Actions
		 */
		initDatabaseActions: function () {
			// Update GeoIP Database
			$('#update-geoip-database').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $result = $('#database-action-result');

				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$result.hide();

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
							$result.removeClass('notice-error').addClass('notice-success');
							$result.find('p').text(response.data.message || 'Database updated successfully!');
							$result.slideDown();

							// Reload after 2 seconds
							setTimeout(function () {
								location.reload();
							}, 2000);
						} else {
							$result.removeClass('notice-success').addClass('notice-error');
							$result.find('p').text(response.data.message || 'Error updating database');
							$result.slideDown();
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$result.removeClass('notice-success').addClass('notice-error');
						$result.find('p').text('Error updating database');
						$result.slideDown();
					}
				});
			});

			// Clear GeoIP Cache
			$('#clear-geoip-cache').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $result = $('#database-action-result');

				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$result.hide();

				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_clear_cache',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$result.removeClass('notice-error').addClass('notice-success');
							$result.find('p').text(response.data.message || 'Cache cleared successfully!');
							$result.slideDown();

							setTimeout(function () {
								$result.slideUp();
							}, 3000);
						} else {
							$result.removeClass('notice-success').addClass('notice-error');
							$result.find('p').text(response.data.message || 'Error clearing cache');
							$result.slideDown();
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$result.removeClass('notice-success').addClass('notice-error');
						$result.find('p').text('Error clearing cache');
						$result.slideDown();
					}
				});
			});
		},

		/**
		 * Initialize Settings Import/Export
		 */
		initSettingsImportExport: function () {
			// Export Settings
			$('#export-settings-button').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);

				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_export_settings',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						if (response.success && response.data.data) {
							// Create download link
							const blob = new Blob([response.data.data], { type: 'application/json' });
							const url = window.URL.createObjectURL(blob);
							const a = document.createElement('a');
							a.href = url;
							a.download = response.data.filename || 'geo-ip-blocker-settings.json';
							document.body.appendChild(a);
							a.click();
							window.URL.revokeObjectURL(url);
							document.body.removeChild(a);
						} else {
							alert('Error exporting settings');
						}
					},
					error: function () {
						alert('Error exporting settings');
					}
				});
			});

			// Import Settings
			$('#import-settings-button').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $fileInput = $('#import-settings-file');
				const $spinner = $button.siblings('.spinner');
				const $result = $('#import-result');
				const file = $fileInput[0].files[0];

				if (!file) {
					alert('Please select a file');
					return;
				}

				if (!confirm('This will overwrite current settings. Continue?')) {
					return;
				}

				const reader = new FileReader();
				reader.onload = function (e) {
					const contents = e.target.result;

					$spinner.addClass('is-active');
					$button.prop('disabled', true);
					$result.hide();

					$.ajax({
						url: geoIPBlockerSettings.ajaxUrl,
						type: 'POST',
						data: {
							action: 'geo_ip_blocker_import_settings',
							nonce: geoIPBlockerSettings.nonce,
							settings_data: contents
						},
						success: function (response) {
							$spinner.removeClass('is-active');
							$button.prop('disabled', false);

							if (response.success) {
								$result.removeClass('notice-error').addClass('notice-success');
								$result.find('p').text(response.data.message || 'Settings imported successfully!');
								$result.slideDown();

								// Reload after 2 seconds
								setTimeout(function () {
									location.reload();
								}, 2000);
							} else {
								$result.removeClass('notice-success').addClass('notice-error');
								$result.find('p').text(response.data.message || 'Error importing settings');
								$result.slideDown();
							}
						},
						error: function () {
							$spinner.removeClass('is-active');
							$button.prop('disabled', false);
							$result.removeClass('notice-success').addClass('notice-error');
							$result.find('p').text('Error importing settings');
							$result.slideDown();
						}
					});
				};

				reader.readAsText(file);
			});

			// Reset Settings
			$('#reset-settings-button').on('click', function (e) {
				e.preventDefault();

				if (!confirm('This will reset all settings to default values. This action cannot be undone. Continue?')) {
					return;
				}

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $result = $('#reset-result');

				$spinner.addClass('is-active');
				$button.prop('disabled', true);
				$result.hide();

				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_reset_settings',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$result.removeClass('notice-error').addClass('notice-success');
							$result.find('p').text(response.data.message || 'Settings reset successfully!');
							$result.slideDown();

							// Reload after 2 seconds
							setTimeout(function () {
								location.reload();
							}, 2000);
						} else {
							$result.removeClass('notice-success').addClass('notice-error');
							$result.find('p').text(response.data.message || 'Error resetting settings');
							$result.slideDown();
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						$result.removeClass('notice-success').addClass('notice-error');
						$result.find('p').text('Error resetting settings');
						$result.slideDown();
					}
				});
			});
		},

		/**
		 * Initialize Debug Tools
		 */
		initDebugTools: function () {
			// Copy System Info
			$('#copy-system-info').on('click', function (e) {
				e.preventDefault();

				const $table = $(this).siblings('table');
				let text = 'Geo & IP Blocker - System Information\n\n';

				$table.find('tr').each(function () {
					const label = $(this).find('th').text();
					const value = $(this).find('td').text();
					text += label + ' ' + value + '\n';
				});

				// Copy to clipboard
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						alert('System information copied to clipboard!');
					}).catch(function () {
						ToolsTab.fallbackCopyToClipboard(text);
					});
				} else {
					ToolsTab.fallbackCopyToClipboard(text);
				}
			});

			// View Debug Log
			$('#view-debug-log').on('click', function (e) {
				e.preventDefault();

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $viewer = $('#debug-log-viewer');

				$spinner.addClass('is-active');
				$button.prop('disabled', true);

				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_view_debug_log',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$viewer.find('textarea').val(response.data.content || 'Log empty');
							$viewer.slideDown();
						} else {
							alert(response.data.message || 'Error viewing log');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						alert('Error viewing log');
					}
				});
			});

			// Clear Debug Log
			$('#clear-debug-log').on('click', function (e) {
				e.preventDefault();

				if (!confirm('Clear debug log?')) {
					return;
				}

				const $button = $(this);
				const $spinner = $button.siblings('.spinner');
				const $viewer = $('#debug-log-viewer');

				$spinner.addClass('is-active');
				$button.prop('disabled', true);

				$.ajax({
					url: geoIPBlockerSettings.ajaxUrl,
					type: 'POST',
					data: {
						action: 'geo_ip_blocker_clear_debug_log',
						nonce: geoIPBlockerSettings.nonce
					},
					success: function (response) {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);

						if (response.success) {
							$viewer.find('textarea').val('');
							$viewer.slideUp();
							alert(response.data.message || 'Log cleared successfully!');
						} else {
							alert(response.data.message || 'Error clearing log');
						}
					},
					error: function () {
						$spinner.removeClass('is-active');
						$button.prop('disabled', false);
						alert('Error clearing log');
					}
				});
			});
		},

		/**
		 * Fallback copy to clipboard for older browsers
		 */
		fallbackCopyToClipboard: function (text) {
			const textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			document.body.appendChild(textArea);
			textArea.select();

			try {
				document.execCommand('copy');
				alert('Informações do sistema copiadas para a área de transferência!');
			} catch (err) {
				alert('Error copying to clipboard');
			}

			document.body.removeChild(textArea);
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

		// Initialize tools tab if on tools tab
		if ($('.geo-ip-blocker-tools-section').length) {
			ToolsTab.init();
		}
	});

})(jQuery);
