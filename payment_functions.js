// Payment Modal Variables
let selectedPaymentType = 'cash';
let cashInputValue = '0';
let cardAmount = 0;

// Show Close Order Modal
function showCloseOrderModal() {
    if (!currentOrderId) {
        showNotification('No active order to close', 'No Order');
        return;
    }

    // Reset payment modal
    selectedPaymentType = 'cash';
    cashInputValue = '0';
    cardAmount = 0;

    const total = parseFloat(document.getElementById('total').textContent);
    document.getElementById('paymentSubtotal').textContent = total.toFixed(2);
    document.getElementById('paymentTotal').textContent = total.toFixed(2);
    document.getElementById('paymentCashInput').value = '0.00';
    
    // Reset payment type buttons
    selectPaymentType('cash');
    
    // Calculate initial balance
    updatePaymentCalculations();
    
    document.getElementById('closeOrderModal').classList.remove('hidden');
}

// Select Payment Type
function selectPaymentType(type) {
    selectedPaymentType = type;
    
    // Reset all buttons
    document.querySelectorAll('.payment-type-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'border-blue-400');
        btn.classList.add('bg-gray-700');
    });
    
    // Highlight selected button
    const btnMap = {
        'cash': 'paymentTypeCash',
        'card': 'paymentTypeCard',
        'card_cash': 'paymentTypeCardCash',
        'credit': 'paymentTypeCredit'
    };
    
    const selectedBtn = document.getElementById(btnMap[type]);
    if (selectedBtn) {
        selectedBtn.classList.remove('bg-gray-700');
        selectedBtn.classList.add('bg-blue-600', 'border-blue-400');
    }
    
    // Show/hide card amount row
    if (type === 'card_cash') {
        document.getElementById('cardAmountRow').style.display = 'flex';
    } else {
        document.getElementById('cardAmountRow').style.display = 'none';
    }
    
    // Reset cash input
    cashInputValue = '0';
    document.getElementById('paymentCashInput').value = '0.00';
    
    updatePaymentCalculations();
}

// Number Pad Functions
function appendNumber(num) {
    if (cashInputValue === '0' && num !== '.') {
        cashInputValue = num;
    } else if (num === '.' && cashInputValue.includes('.')) {
        return; // Don't allow multiple decimal points
    } else {
        cashInputValue += num;
    }
    
    document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
    updatePaymentCalculations();
}

function backspaceNumber() {
    if (cashInputValue.length > 1) {
        cashInputValue = cashInputValue.slice(0, -1);
    } else {
        cashInputValue = '0';
    }
    
    document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
    updatePaymentCalculations();
}

function clearNumber() {
    cashInputValue = '0';
    document.getElementById('paymentCashInput').value = '0.00';
    updatePaymentCalculations();
}

// Update Payment Calculations
function updatePaymentCalculations() {
    const total = parseFloat(document.getElementById('paymentTotal').textContent);
    const cashAmount = parseFloat(cashInputValue) || 0;
    
    let balance = 0;
    let credit = 0;
    
    if (selectedPaymentType === 'cash') {
        balance = cashAmount - total;
        if (balance < 0) {
            credit = Math.abs(balance);
            balance = 0;
        }
    } else if (selectedPaymentType === 'card') {
        // Card payment - no cash needed
        document.getElementById('paymentCashInput').value = '0.00';
        balance = 0;
        credit = 0;
    } else if (selectedPaymentType === 'card_cash') {
        // Split payment
        balance = cashAmount - (total - cardAmount);
        if (balance < 0) {
            credit = Math.abs(balance);
            balance = 0;
        }
    } else if (selectedPaymentType === 'credit') {
        // Credit - full amount is credit
        credit = total;
        balance = 0;
    }
    
    // Update display
    document.getElementById('paymentBalance').textContent = balancetoFixed(2);
    document.getElementById('paymentCredit').textContent = credit.toFixed(2);
    
    // Show/hide credit row
    if (credit > 0) {
        document.getElementById('creditRow').style.display = 'flex';
        document.getElementById('balanceRow').style.display = 'none';
    } else {
        document.getElementById('creditRow').style.display = 'none';
        document.getElementById('balanceRow').style.display = 'flex';
    }
}

// Complete Payment
async function completePayment() {
    const total = parseFloat(document.getElementById('paymentTotal').textContent);
    const cashAmount = parseFloat(cashInputValue) || 0;
    
    // Validation
    if (selectedPaymentType === 'cash' && cashAmount < total) {
        showNotification('Cash amount is less than total. Please enter sufficient amount.', 'Payment Error');
        return;
    }
    
    if (!currentOrderId) {
        showNotification('No active order to close', 'Error');
        return;
    }

    try {
        const response = await fetch('/pos/payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                order_id: currentOrderId,
                payment_method: selectedPaymentType,
                amount_paid: selectedPaymentType === 'card' ? total : cashAmount
            })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Payment completed successfully! Receipt ready to print.', 'Success');

            // Clear POS state
            billItems = [];
            currentOrderId = null;
            currentOrderType = null;
            selectedTableId = null;

            renderBill();
            calculateTotals();
            closeModal('closeOrderModal');

            // Update order type display
            const display = document.getElementById('orderTypeDisplay');
            if (display) display.textContent = 'Select Order Type';

            // Hide menu
            document.getElementById('menuSelectionContainer').classList.remove('flex');
            document.getElementById('menuSelectionContainer').classList.add('hidden');
            const msg = document.getElementById('initialStateMessage');
            if (msg) msg.classList.remove('hidden');

            // Optional: Auto-open print receipt
            // window.open('/pos/receipt/' + result.order.id, '_blank');
        } else {
            showNotification('Error: ' + (result.message || 'Unknown error'), 'Payment Error');
        }
    } catch (error) {
        console.error('Payment error:', error);
        showNotification('Error processing payment: ' + error.message, 'System Error');
    }
}
