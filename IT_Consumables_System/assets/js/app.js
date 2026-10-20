// app.js - small progressive-enhancement helpers (no external dependencies)

// Confirm before any delete form is submitted.
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.matches('form[data-confirm]')) {
        const message = form.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(message)) {
            e.preventDefault();
        }
    }
});

// On the checkout / return pages: show the currently available stock
// and unit of measure for the selected item as a live hint.
document.addEventListener('DOMContentLoaded', function () {
    const itemSelect = document.getElementById('item_id');
    const hint = document.getElementById('stockHint');
    const qtyInput = document.getElementById('quantity');

    if (itemSelect && hint) {
        const updateHint = function () {
            const opt = itemSelect.options[itemSelect.selectedIndex];
            if (opt && opt.value) {
                const stock = opt.getAttribute('data-stock');
                const unit = opt.getAttribute('data-unit');
                hint.textContent = 'Available: ' + stock + ' ' + unit;
                if (qtyInput) { qtyInput.max = stock; }
            } else {
                hint.textContent = '';
            }
        };
        itemSelect.addEventListener('change', updateHint);
        updateHint();
    }
});
