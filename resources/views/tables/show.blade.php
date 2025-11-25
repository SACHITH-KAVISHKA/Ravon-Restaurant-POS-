@extends('layouts.app')

@section('title', 'Table ' . $table->table_number . ' - Ravon POS')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('tables.index') }}" class="text-blue-400 hover:text-blue-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">Table {{ $table->table_number }}</h1>
                <p class="text-gray-400">{{ $table->floor->name }} • Capacity: {{ $table->capacity }}</p>
            </div>
        </div>
        
        @if($table->currentOrder)
        <a href="{{ route('payments.show', $table->currentOrder->id) }}" 
           class="touch-button bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-3 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 shadow-lg">
            Process Payment
        </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Menu Items (Left Side) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-gray-900/50 border border-gray-800 rounded-xl shadow-xl">
                <div class="p-6 border-b border-gray-800">
                    <h2 class="text-xl font-bold text-white">Menu Items</h2>
                </div>
                
                <div class="p-6">
                    @foreach($categories as $category)
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                            {{ $category->name }}
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($category->items as $item)
                            <div class="bg-black/30 border border-gray-800 rounded-lg p-4 hover:border-blue-600/50 transition cursor-pointer"
                                 onclick="addToOrder({{ $item->id }}, '{{ $item->name }}', {{ $item->price }})">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-semibold text-white">{{ $item->name }}</h4>
                                    <span class="text-blue-400 font-bold">Rs. {{ number_format($item->price, 2) }}</span>
                                </div>
                                @if($item->description)
                                <p class="text-sm text-gray-400 mb-2">{{ $item->description }}</p>
                                @endif
                                <div class="flex items-center space-x-2">
                                    @if($item->is_vegetarian)
                                    <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded">Veg</span>
                                    @endif
                                    @if($item->is_spicy)
                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded">🌶️ Spicy</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Current Order (Right Side) -->
        <div class="lg:col-span-1">
            <div class="bg-gray-900/50 border border-gray-800 rounded-xl shadow-xl sticky top-4">
                <div class="p-6 border-b border-gray-800">
                    <h2 class="text-xl font-bold text-white">Current Order</h2>
                    @if($table->currentOrder)
                    <p class="text-sm text-gray-400">{{ $table->currentOrder->order_number }}</p>
                    @endif
                </div>
                
                <div id="orderItems" class="p-6 max-h-96 overflow-y-auto">
                    @if($table->currentOrder && $table->currentOrder->items->count() > 0)
                        @foreach($table->currentOrder->items as $orderItem)
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-800" data-item-id="{{ $orderItem->id }}">
                            <div class="flex-1">
                                <h4 class="font-semibold text-white">{{ $orderItem->item->name }}</h4>
                                <p class="text-sm text-gray-400">Rs. {{ number_format($orderItem->unit_price, 2) }} x {{ $orderItem->quantity }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-white font-bold">Rs. {{ number_format($orderItem->subtotal, 2) }}</span>
                                <button onclick="removeItem({{ $orderItem->id }})" class="text-red-400 hover:text-red-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    @else
                    <p class="text-center text-gray-500 py-8">No items added yet</p>
                    @endif
                </div>
                
                <div class="p-6 border-t border-gray-800 bg-black/50">
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-gray-400">
                            <span>Subtotal:</span>
                            <span id="subtotal">Rs. {{ number_format($table->currentOrder->subtotal ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>Tax (10%):</span>
                            <span id="tax">Rs. {{ number_format($table->currentOrder->tax_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-white pt-2 border-t border-gray-700">
                            <span>Total:</span>
                            <span id="total">Rs. {{ number_format($table->currentOrder->total_amount ?? 0, 2) }}</span>
                        </div>
                    </div>
                    
                    <button onclick="placeOrder()" id="placeOrderBtn"
                            class="w-full touch-button bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 shadow-lg">
                        Add Items to Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToOrder(itemId, itemName, price) {
    const existingItem = cart.find(item => item.item_id === itemId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            item_id: itemId,
            name: itemName,
            price: price,
            quantity: 1
        });
    }
    
    updateCartDisplay();
}

function updateCartDisplay() {
    if (cart.length === 0) return;
    
    const orderItemsDiv = document.getElementById('orderItems');
    let html = '';
    let subtotal = 0;
    
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        html += `
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-800">
                <div class="flex-1">
                    <h4 class="font-semibold text-white">${item.name}</h4>
                    <p class="text-sm text-gray-400">Rs. ${item.price.toFixed(2)} x ${item.quantity}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-white font-bold">Rs. ${itemTotal.toFixed(2)}</span>
                    <button onclick="removeFromCart(${index})" class="text-red-400 hover:text-red-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
    });
    
    const tax = subtotal * 0.10;
    const total = subtotal + tax;
    
    orderItemsDiv.innerHTML = html;
    document.getElementById('subtotal').textContent = 'Rs. ' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = 'Rs. ' + tax.toFixed(2);
    document.getElementById('total').textContent = 'Rs. ' + total.toFixed(2);
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function removeItem(itemId) {
    if (!confirm('Remove this item from the order?')) return;
    
    fetch(`/orders/items/${itemId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Failed to remove item', 'error');
        console.error('Error:', error);
    });
}

function placeOrder() {
    if (cart.length === 0) {
        showToast('Please add items to the order first', 'error');
        return;
    }
    
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    fetch('/orders', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            table_id: {{ $table->id }},
            items: cart
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            cart = [];
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Add Items to Order';
        }
    })
    .catch(error => {
        showToast('Failed to place order', 'error');
        console.error('Error:', error);
        btn.disabled = false;
        btn.textContent = 'Add Items to Order';
    });
}
</script>
@endsection
