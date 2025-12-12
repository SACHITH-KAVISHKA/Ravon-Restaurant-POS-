@extends('layouts.app')

@section('title', 'Payment - Ravon POS')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('payments.index') }}" class="text-purple-600 hover:text-purple-6002">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">Process Payment</h1>
                <p class="text-gray-800-muted">{{ $order->order_number }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order Details -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-300">
                <h2 class="text-xl font-bold text-gray-800">Order Details</h2>
                @if($order->table)
                <p class="text-sm text-gray-800-muted">Table {{ $order->table->table_number }}</p>
                @endif
            </div>
            
            <div class="p-6">
                <div class="space-y-3 mb-6">
                    @foreach($order->items as $item)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                        <div>
                            <h4 class="font-semibold text-gray-800">{{ $item->item->name }}</h4>
                            <p class="text-sm text-gray-800-muted">Rs. {{ number_format($item->unit_price, 2) }} x {{ $item->quantity }}</p>
                        </div>
                        <span class="text-gray-800 font-bold">Rs. {{ number_format($item->subtotal, 2) }}</span>
                    </div>
                    @endforeach
                </div>
                
                <div class="space-y-2 bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between text-gray-800-muted">
                        <span>Subtotal:</span>
                        <span>Rs. {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-800-muted">
                        <span>Tax (10%):</span>
                        <span>Rs. {{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-green-600">
                        <span>Discount:</span>
                        <span>- Rs. {{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-2xl font-bold text-gray-800 pt-3 border-t border-gray-300">
                        <span>Total:</span>
                        <span id="orderTotal">Rs. {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-md">
            <div class="p-6 border-b border-gray-300">
                <h2 class="text-xl font-bold text-gray-800">Payment Method</h2>
            </div>
            
            <div class="p-6">
                <form id="paymentForm">
                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label class="block text-gray-800-muted text-sm font-bold mb-3">Select Payment Method</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" onclick="selectPaymentMethod('cash')" 
                                    class="payment-method-btn active p-4 rounded-lg border-2 border-purple-600 bg-purple-100 text-gray-800 font-bold hover:bg-ravon-gold/30 transition">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Cash
                            </button>
                            
                            <button type="button" onclick="selectPaymentMethod('card')" 
                                    class="payment-method-btn p-4 rounded-lg border-2 border-gray-300 bg-gray-50 text-gray-800 font-bold hover:border-purple-600 hover:bg-purple-100 transition">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Card
                            </button>
                            
                            <button type="button" onclick="selectPaymentMethod('upi')" 
                                    class="payment-method-btn p-4 rounded-lg border-2 border-gray-300 bg-gray-50 text-gray-800 font-bold hover:border-purple-600 hover:bg-purple-100 transition">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                UPI
                            </button>
                            
                            <button type="button" onclick="selectPaymentMethod('other')" 
                                    class="payment-method-btn p-4 rounded-lg border-2 border-gray-300 bg-gray-50 text-gray-800 font-bold hover:border-purple-600 hover:bg-purple-100 transition">
                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Other
                            </button>
                        </div>
                        <input type="hidden" id="payment_method" name="payment_method" value="cash">
                    </div>

                    <!-- Amount Received -->
                    <div class="mb-6">
                        <label class="block text-gray-800-muted text-sm font-bold mb-2">Amount Received</label>
                        <input type="number" id="amount_received" step="0.01" min="{{ $order->total_amount }}"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-3 text-gray-800 text-xl font-bold focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                               placeholder="0.00" oninput="calculateChange()">
                    </div>

                    <!-- Change Amount -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-800-muted font-bold">Change:</span>
                            <span id="changeAmount" class="text-2xl font-bold text-green-600">Rs. 0.00</span>
                        </div>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="mb-6">
                        <label class="block text-gray-800-muted text-sm font-bold mb-2">Quick Amount</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" onclick="setAmount({{ ceil($order->total_amount / 100) * 100 }})" 
                                    class="bg-gray-50 border border-gray-300 text-gray-800 py-2 rounded-lg hover:border-purple-600 hover:bg-purple-100 transition">
                                Rs. {{ number_format(ceil($order->total_amount / 100) * 100, 0) }}
                            </button>
                            <button type="button" onclick="setAmount({{ ceil($order->total_amount / 500) * 500 }})" 
                                    class="bg-gray-50 border border-gray-300 text-gray-800 py-2 rounded-lg hover:border-purple-600 hover:bg-purple-100 transition">
                                Rs. {{ number_format(ceil($order->total_amount / 500) * 500, 0) }}
                            </button>
                            <button type="button" onclick="setAmount({{ ceil($order->total_amount / 1000) * 1000 }})" 
                                    class="bg-gray-50 border border-gray-300 text-gray-800 py-2 rounded-lg hover:border-purple-600 hover:bg-purple-100 transition">
                                Rs. {{ number_format(ceil($order->total_amount / 1000) * 1000, 0) }}
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                            class="w-full touch-button bg-green-600 hover:bg-green-600/90 text-white py-4 rounded-lg font-bold text-lg shadow-lg transition">
                        Complete Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const orderTotal = {{ $order->total_amount }};

function selectPaymentMethod(method) {
    document.getElementById('payment_method').value = method;
    
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('active', 'border-purple-600', 'bg-purple-100');
        btn.classList.add('border-gray-300', 'bg-gray-50');
    });
    
    event.target.closest('.payment-method-btn').classList.add('active', 'border-purple-600', 'bg-purple-100');
    event.target.closest('.payment-method-btn').classList.remove('border-gray-300', 'bg-gray-50');
}

function setAmount(amount) {
    document.getElementById('amount_received').value = amount;
    calculateChange();
}

function calculateChange() {
    const received = parseFloat(document.getElementById('amount_received').value) || 0;
    const change = Math.max(0, received - orderTotal);
    document.getElementById('changeAmount').textContent = 'Rs. ' + change.toFixed(2);
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const amountReceived = parseFloat(document.getElementById('amount_received').value);
    
    if (!amountReceived || amountReceived < orderTotal) {
        showToast('Amount received must be at least Rs. ' + orderTotal.toFixed(2), 'error');
        return;
    }
    
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    fetch('/payments/{{ $order->id }}/process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            payment_method: document.getElementById('payment_method').value,
            amount_received: amountReceived
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Payment completed successfully!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route('payments.index') }}';
            }, 1500);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Complete Payment';
        }
    })
    .catch(error => {
        showToast('Payment failed', 'error');
        console.error('Error:', error);
        btn.disabled = false;
        btn.textContent = 'Complete Payment';
    });
});

// Set exact amount on page load
document.getElementById('amount_received').value = orderTotal;
calculateChange();
</script>
@endsection



