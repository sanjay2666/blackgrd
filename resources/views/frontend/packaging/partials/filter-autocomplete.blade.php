<script>
(function () {
    function bindPackagingAutocomplete(inputSelector, hiddenSelector, endpoint, labelFor, idFor) {
        var $input = $(inputSelector);
        var $hidden = hiddenSelector ? $(hiddenSelector) : $();

        if (!$input.length) {
            return;
        }

        $input.data('selected-label', $input.val()).on('input', function () {
            if ($hidden.length && $(this).val() !== $(this).data('selected-label')) {
                $hidden.val('');
            }
        }).autocomplete({
            minLength: 2,
            delay: 150,
            source: function (request, response) {
                $.getJSON(endpoint, {term: request.term}, function (items) {
                    response($.map(items, function (item) {
                        var label = labelFor(item);

                        return {id: idFor(item), label: label, value: label};
                    }));
                });
            },
            select: function (event, ui) {
                $input.val(ui.item.label).data('selected-label', ui.item.label);
                if ($hidden.length) {
                    $hidden.val(ui.item.id);
                }

                return false;
            }
        });
    }

    bindPackagingAutocomplete('#packaging-customer-search', '#packaging-customer-id', '{{ route('list_customer') }}', function (item) {
        return item.name || item.company_name;
    }, function (item) {
        return item.id;
    });
    bindPackagingAutocomplete('#packaging-item-search', '#packaging-item-id', '{{ route('list_item') }}', function (item) {
        return item.item_name;
    }, function (item) {
        return item.item_id;
    });
    bindPackagingAutocomplete('#packaging-sale-order-search', '#packaging-sale-order-id', '{{ route('find_saleOrderNumer') }}', function (item) {
        return item.sale_order_number;
    }, function (item) {
        return item.id;
    });
    bindPackagingAutocomplete('#packaging-lot-search', null, '{{ route('packaging.lot-autocomplete') }}', function (item) {
        return item.label;
    }, function (item) {
        return item.id;
    });
}());
</script>
