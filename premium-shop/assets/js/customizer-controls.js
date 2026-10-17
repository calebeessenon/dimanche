/**
 * Premium Shop — Customizer: sortable homepage sections.
 */
(function ($, api) {
	'use strict';

	function serialize($list) {
		return $list.children('.ps-sortable__item').map(function () {
			var $item = $(this);
			return $item.data('id') + ':' + ($item.find('input[type="checkbox"]').is(':checked') ? '1' : '0');
		}).get().join(',');
	}

	function init($list) {
		var $input = $list.siblings('input[type="hidden"]');
		var save = function () { $input.val(serialize($list)).trigger('change'); };

		$list.sortable({
			handle: '.ps-sortable__handle',
			axis: 'y',
			update: save
		});
		$list.on('change', 'input[type="checkbox"]', save);
		$list.on('click', '[data-move]', function () {
			var $item = $(this).closest('.ps-sortable__item');
			if ($(this).data('move') === 'up') {
				$item.prev().before($item);
			} else {
				$item.next().after($item);
			}
			save();
			$(this).trigger('focus');
		});
	}

	api.bind('ready', function () {
		$('[data-ps-sortable]').each(function () { init($(this)); });
	});
}(jQuery, wp.customize));
