/**
 * Swatch — front-end behaviour.
 *
 * Wires the rendered swatch groups to WooCommerce's native variations form:
 *   - clicking a swatch sets the matching hidden <select> value and dispatches a
 *     `change` event, so WooCommerce recalculates the available variations,
 *     price, stock and the add-to-cart button exactly as it would for the
 *     dropdown.
 *   - WooCommerce's own events are reflected back onto the swatches:
 *       `woocommerce_update_variation_values` -> enable/disable swatches that
 *           are (not) part of a valid combination;
 *       `reset_data` / `reset_image` -> clear the selection.
 *
 * No jQuery: we use native DOM APIs and listen for the jQuery-triggered custom
 * events on the form element (they bubble as native events on modern WC).
 *
 * Accessibility: each group is a radiogroup; swatches are role="radio" and are
 * operable with the arrow keys, Home/End and Space/Enter.
 */
(function () {
	'use strict';

	var config = window.swatchConfig || {};

	function hideSelect(select) {
		// Keep it in the DOM and functional, just visually hidden + out of tab order.
		select.classList.add('swatch-hidden-select');
		select.setAttribute('data-swatch-bound', '1');
		select.setAttribute('tabindex', '-1');
		select.setAttribute('aria-hidden', 'true');
	}

	function buttons(group) {
		return Array.prototype.slice.call(group.querySelectorAll('.swatch'));
	}

	function setSelected(group, select, value) {
		buttons(group).forEach(function (btn) {
			var on = btn.getAttribute('data-swatch-value') === value && value !== '';
			btn.setAttribute('aria-checked', on ? 'true' : 'false');
			btn.classList.toggle('is-selected', on);
			btn.setAttribute('tabindex', on ? '0' : '-1');
		});

		// Ensure at least one swatch is tabbable when nothing is selected.
		if (value === '') {
			var first = group.querySelector('.swatch:not([disabled])') || group.querySelector('.swatch');
			if (first) {
				first.setAttribute('tabindex', '0');
			}
		}

		updateSelectedLabel(group, value);
	}

	function updateSelectedLabel(group, value) {
		if (!config.showSelectedLabel) {
			return;
		}

		var label = group.querySelector('.swatch-group__selected');
		var text = '';

		if (value !== '') {
			var active = group.querySelector('.swatch[data-swatch-value="' + cssEscape(value) + '"]');
			if (active) {
				text = active.getAttribute('aria-label') || '';
			}
		}

		if (!label) {
			label = document.createElement('span');
			label.className = 'swatch-group__selected';
			group.appendChild(label);
		}

		label.textContent = text;
	}

	function cssEscape(value) {
		if (window.CSS && window.CSS.escape) {
			return window.CSS.escape(value);
		}
		return String(value).replace(/["\\\]]/g, '\\$&');
	}

	function chooseValue(group, select, value) {
		// Toggle off when re-clicking the active swatch.
		if (select.value === value) {
			value = '';
		}

		select.value = value;
		select.dispatchEvent(new Event('change', { bubbles: true }));
		setSelected(group, select, select.value);
	}

	function onKeydown(group, select, e) {
		var all = buttons(group).filter(function (b) {
			return !b.disabled;
		});
		var idx = all.indexOf(e.target);
		if (idx === -1) {
			return;
		}

		var next = null;

		switch (e.key) {
			case 'ArrowRight':
			case 'ArrowDown':
				next = all[(idx + 1) % all.length];
				break;
			case 'ArrowLeft':
			case 'ArrowUp':
				next = all[(idx - 1 + all.length) % all.length];
				break;
			case 'Home':
				next = all[0];
				break;
			case 'End':
				next = all[all.length - 1];
				break;
			case ' ':
			case 'Enter':
				e.preventDefault();
				chooseValue(group, select, e.target.getAttribute('data-swatch-value'));
				return;
			default:
				return;
		}

		if (next) {
			e.preventDefault();
			next.focus();
		}
	}

	function syncEnabledStates(form) {
		// WooCommerce updates each <select> option's disabled flag for the
		// current combination; mirror that onto the swatches.
		form.querySelectorAll('.swatch-group[data-swatch-for]').forEach(function (group) {
			var select = document.getElementById(group.getAttribute('data-swatch-for'));
			if (!select) {
				return;
			}

			buttons(group).forEach(function (btn) {
				var value = btn.getAttribute('data-swatch-value');
				var option = select.querySelector('option[value="' + cssEscape(value) + '"]');
				var disabled = !option || option.disabled;
				btn.disabled = disabled;
				btn.classList.toggle('is-disabled', disabled);
				btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
			});

			setSelected(group, select, select.value);
		});
	}

	function initForm(form) {
		var groups = form.querySelectorAll('.swatch-group[data-swatch-for]');
		if (!groups.length) {
			return;
		}

		groups.forEach(function (group) {
			var select = document.getElementById(group.getAttribute('data-swatch-for'));
			if (!select || select.getAttribute('data-swatch-bound')) {
				return;
			}

			hideSelect(select);

			group.addEventListener('click', function (e) {
				var btn = e.target.closest('.swatch');
				if (!btn || btn.disabled || !group.contains(btn)) {
					return;
				}
				chooseValue(group, select, btn.getAttribute('data-swatch-value'));
			});

			group.addEventListener('keydown', function (e) {
				onKeydown(group, select, e);
			});

			// Reflect external changes to the select (e.g. WooCommerce reset).
			select.addEventListener('change', function () {
				setSelected(group, select, select.value);
			});

			setSelected(group, select, select.value);
		});

		// WooCommerce variation lifecycle events bubble to the form.
		form.addEventListener('woocommerce_update_variation_values', function () {
			syncEnabledStates(form);
		});
		form.addEventListener('reset_data', function () {
			groups.forEach(function (group) {
				var select = document.getElementById(group.getAttribute('data-swatch-for'));
				if (select) {
					setSelected(group, select, select.value);
				}
			});
		});
	}

	function init() {
		document.querySelectorAll('form.variations_form').forEach(initForm);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
