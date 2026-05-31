document.querySelectorAll('.toast').forEach((toastElement) => {
    const toast = bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3500 });
    toast.show();
});

const purchaseForm = document.querySelector('[data-purchase-form]');

if (purchaseForm) {
    const rowsContainer = purchaseForm.querySelector('[data-purchase-items]');
    const addButton = purchaseForm.querySelector('[data-add-purchase-row]');
    const totalElement = purchaseForm.querySelector('[data-purchase-total]');

    const updateRow = (row) => {
        const quantity = Number(row.querySelector('[data-quantity]').value || 0);
        const unitPrice = Number(row.querySelector('[data-unit-price]').value || 0);
        row.querySelector('[data-subtotal]').value = (quantity * unitPrice).toFixed(2);
    };

    const updateTotal = () => {
        let total = 0;
        rowsContainer.querySelectorAll('[data-purchase-row]').forEach((row) => {
            updateRow(row);
            total += Number(row.querySelector('[data-subtotal]').value || 0);
        });
        totalElement.textContent = total.toFixed(2);
    };

    const bindRow = (row) => {
        const medicineSelect = row.querySelector('[data-medicine-select]');
        const priceInput = row.querySelector('[data-unit-price]');
        const removeButton = row.querySelector('[data-remove-row]');

        medicineSelect.addEventListener('change', () => {
            const option = medicineSelect.selectedOptions[0];
            if (option && option.dataset.price) {
                priceInput.value = Number(option.dataset.price).toFixed(2);
            }
            updateTotal();
        });

        row.querySelector('[data-quantity]').addEventListener('input', updateTotal);
        priceInput.addEventListener('input', updateTotal);
        removeButton.addEventListener('click', () => {
            const rows = rowsContainer.querySelectorAll('[data-purchase-row]');
            if (rows.length > 1) {
                row.remove();
                updateTotal();
            }
        });
    };

    addButton.addEventListener('click', () => {
        const firstRow = rowsContainer.querySelector('[data-purchase-row]');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('[data-medicine-select]').value = '';
        clone.querySelector('[data-quantity]').value = '1';
        clone.querySelector('[data-unit-price]').value = '0.00';
        clone.querySelector('[data-subtotal]').value = '0.00';
        rowsContainer.appendChild(clone);
        bindRow(clone);
    });

    rowsContainer.querySelectorAll('[data-purchase-row]').forEach(bindRow);
    updateTotal();
}

const saleForm = document.querySelector('[data-sale-form]');

if (saleForm) {
    const rowsContainer = saleForm.querySelector('[data-sale-items]');
    const addButton = saleForm.querySelector('[data-sale-add-row]');
    const totalElement = saleForm.querySelector('[data-sale-total]');
    const changeElement = saleForm.querySelector('[data-sale-change]');
    const paidInput = saleForm.querySelector('[data-sale-paid]');

    const updateRow = (row) => {
        const quantityInput = row.querySelector('[data-sale-quantity]');
        const priceInput = row.querySelector('[data-sale-unit-price]');
        const medicineSelect = row.querySelector('[data-sale-medicine]');
        const option = medicineSelect.selectedOptions[0];
        const stock = option && option.dataset.stock ? Number(option.dataset.stock) : 0;

        if (stock > 0) {
            quantityInput.max = String(stock);
        }

        const quantity = Number(quantityInput.value || 0);
        const unitPrice = Number(priceInput.value || 0);
        row.querySelector('[data-sale-subtotal]').value = (quantity * unitPrice).toFixed(2);
    };

    const updateTotals = () => {
        let total = 0;
        rowsContainer.querySelectorAll('[data-sale-row]').forEach((row) => {
            updateRow(row);
            total += Number(row.querySelector('[data-sale-subtotal]').value || 0);
        });
        const paid = Number(paidInput.value || 0);
        totalElement.textContent = total.toFixed(2);
        changeElement.textContent = Math.max(0, paid - total).toFixed(2);
    };

    const bindRow = (row) => {
        const medicineSelect = row.querySelector('[data-sale-medicine]');
        const priceInput = row.querySelector('[data-sale-unit-price]');
        const removeButton = row.querySelector('[data-sale-remove-row]');

        medicineSelect.addEventListener('change', () => {
            const option = medicineSelect.selectedOptions[0];
            if (option && option.dataset.price) {
                priceInput.value = Number(option.dataset.price).toFixed(2);
            }
            updateTotals();
        });

        row.querySelector('[data-sale-quantity]').addEventListener('input', updateTotals);
        priceInput.addEventListener('input', updateTotals);
        removeButton.addEventListener('click', () => {
            const rows = rowsContainer.querySelectorAll('[data-sale-row]');
            if (rows.length > 1) {
                row.remove();
                updateTotals();
            }
        });
    };

    addButton.addEventListener('click', () => {
        const firstRow = rowsContainer.querySelector('[data-sale-row]');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('[data-sale-medicine]').value = '';
        clone.querySelector('[data-sale-quantity]').value = '1';
        clone.querySelector('[data-sale-unit-price]').value = '0.00';
        clone.querySelector('[data-sale-subtotal]').value = '0.00';
        rowsContainer.appendChild(clone);
        bindRow(clone);
    });

    paidInput.addEventListener('input', updateTotals);
    rowsContainer.querySelectorAll('[data-sale-row]').forEach(bindRow);
    updateTotals();
}
