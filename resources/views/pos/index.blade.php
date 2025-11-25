@extends('layouts.app', ['hideNavigation' => true])

@section('title', 'POS - Ravon Restaurant')

@section('content')
<div class="h-screen flex flex-col bg-gray-900">
    <!-- Top Bar -->
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold text-white">Point of Sale</h1>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-400" id="pos-clock"></span>
            <span class="text-sm text-white font-semibold">{{ Auth::user()->name }}</span>
            <button onclick="lockScreen()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                Lock
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Side - Bill Section -->
        <div class="w-1/3 bg-gray-800 border-r border-gray-700 flex flex-col">
            <!-- Bill Header -->
            <div class="p-4 border-b border-gray-700">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-bold text-white">Bill</h2>
                    <div class="text-sm text-gray-400">
                        <span id="orderTypeDisplay" class="text-white font-semibold">Select Order Type</span>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs font-semibold text-gray-400 pb-2">
                    <div># | Description</div>
                    <div class="text-center">Qty</div>
                    <div class="text-right">Amount</div>
                </div>
            </div>

            <!-- Bill Items -->
            <div id="billItems" class="flex-1 overflow-y-auto p-4 space-y-2">
                <!-- Items will be added here dynamically -->
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-16 h-16 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p>No items added</p>
                </div>
            </div>

            <!-- Bill Totals -->
            <div class="p-4 border-t border-gray-700 bg-gray-900">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-400">
                        <span>Sub Total</span>
                        <span id="subtotal">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Discount</span>
                        <span id="discount">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Service</span>
                        <span id="service">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Tax</span>
                        <span id="tax">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Less Amount</span>
                        <span id="lessAmount">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                        <span>Delivery</span>
                        <span id="delivery">0.00</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-white border-t border-gray-700 pt-2 mt-2">
                        <span>Total</span>
                        <span id="total">0.00</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-3 gap-2 mt-4">
                    <button class="bg-gray-700 text-white py-2 rounded hover:bg-gray-600 transition flex items-center justify-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Quantity
                    </button>
                    <button class="bg-gray-700 text-white py-2 rounded hover:bg-gray-600 transition flex items-center justify-center" onclick="showModifiersModal()">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Modifiers
                    </button>
                    <button class="bg-red-600 text-white py-2 rounded hover:bg-red-700 transition flex items-center justify-center" onclick="voidItem()">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Void
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Side - Menu and Actions -->
        <div class="flex-1 flex flex-col">
            <!-- Menu Tabs -->
            <div id="menuSelectionContainer" class="hidden flex-col h-full">
            <div class="bg-gray-800 border-b border-gray-700">
                <div class="flex overflow-x-auto whitespace-nowrap px-4" id="categoryTabs" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <style>
                        #categoryTabs::-webkit-scrollbar {
                            display: none;
                        }
                    </style>
                    @foreach($categories as $category)
                    <button class="category-tab px-6 py-3 {{ $loop->first ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }} transition font-semibold rounded-t-lg" 
                            onclick="filterByCategory({{ $category->id }})"
                            data-id="{{ $category->id }}">
                        {{ strtoupper($category->name) }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="flex-1 flex overflow-hidden">
                <!-- Menu Items Grid -->
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Category Filters Removed -->

                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="flex gap-2">
                            <input type="text" id="searchItems" placeholder="Search items..." class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Items Grid -->
                    <div class="grid grid-cols-4 gap-3" id="itemsGrid">
                        @foreach($categories as $category)
                            @foreach($category->availableItems as $item)
                            <button class="p-3 bg-gray-700 text-white rounded-lg hover:bg-blue-600 transition border border-gray-600 hover:border-blue-500 text-left" 
                                    onclick="selectItem({{ $item->id }}, '{{ $item->name }}', {{ $item->price }}, {{ json_encode($item->modifiers) }})"
                                    data-category="{{ $category->id }}">
                                <div class="font-semibold text-sm">{{ $item->name }}</div>
                                <div class="text-xs text-gray-400 mt-1">Rs. {{ number_format($item->price, 2) }}</div>
                            </button>
                            @endforeach
                        @endforeach
                    </div>



                    <!-- Scroll Arrows -->
                    <div class="flex justify-center gap-4 mt-4">
                        <button class="p-3 bg-gray-700 text-white rounded-full hover:bg-gray-600 transition" onclick="scrollUp()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button class="p-3 bg-gray-700 text-white rounded-full hover:bg-gray-600 transition" onclick="scrollDown()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                </div>
            </div>
            
            <!-- Initial State Message -->
            <div id="initialStateMessage" class="flex-1 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-xl font-semibold">Select an Order Type to view menu</p>
                </div>
            </div>
        </div>

        <!-- Right Actions Panel (Moved Here) -->
        <div class="w-80 bg-gray-800 border-l border-gray-700 p-3 space-y-2 overflow-y-auto">
            <!-- Portion Selection Area -->
            <div id="portionSelectionArea" class="hidden mb-4 p-3 bg-gray-700 rounded-lg border border-gray-600 relative">
                <button onclick="cancelPortionSelection()" class="absolute top-2 right-2 text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <h3 class="text-lg font-bold text-white mb-2 pr-6">Select Portion</h3>
                <div id="portionOptions" class="grid grid-cols-2 gap-2">
                    <!-- Options injected via JS -->
                </div>
            </div>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openTableOrderModal()">
                <div class="bg-white text-emerald-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div class="bg-emerald-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-emerald-600 transition">
                    Table Order
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openTakeAwayModal()">
                <div class="bg-white text-amber-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div class="bg-amber-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-amber-500 transition">
                    Take Away
                </div>
            </button>


            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="setOrderType('uber-eats', 'Uber Eats')">
                <div class="bg-white text-gray-800 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <span class="font-bold text-sm">Uber</span>
                </div>
                <div class="bg-gray-200 text-gray-800 flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-gray-300 transition">
                    Uber Eats
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="setOrderType('pickme', 'PickMe Food')">
                <div class="bg-white text-pink-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <span class="font-bold text-sm">Pick</span>
                </div>
                <div class="bg-pink-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-pink-500 transition">
                    PickMe Food
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="checkout()">
                <div class="bg-white text-green-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </div>
                <div class="bg-green-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-green-600 transition">
                    Place Order
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openOrderCheckModal()">
                <div class="bg-white text-blue-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div class="bg-blue-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-blue-500 transition">
                    Open Checks
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="clearBill()">
                <div class="bg-white text-red-400 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div class="bg-red-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-red-500 transition">
                    Clear
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="cancelOrder()">
                <div class="bg-white text-red-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="bg-red-600 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-red-700 transition">
                    Cancel
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="splitOrder()">
                <div class="bg-white text-purple-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                <div class="bg-purple-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-purple-500 transition">
                    Split Order
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="mergeOrder()">
                <div class="bg-white text-teal-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="bg-teal-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-teal-500 transition">
                    Merge Order
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="transferTable()">
                <div class="bg-white text-cyan-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="bg-cyan-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-cyan-500 transition">
                    Table Transfer
                </div>
            </button>
            
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="printCopy()">
                <div class="bg-white text-rose-400 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </div>
                <div class="bg-rose-300 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-rose-400 transition">
                    Print Copy
                </div>
            </button>
        </div>



<!-- Table Selection Modal -->
<div id="tableModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-white">Select Table</h2>
            <button onclick="closeModal('tableModal')" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="grid grid-cols-5 gap-4">
            @foreach($tables as $table)
            <button onclick="selectTable('{{ $table->table_number }}', {{ $table->id }})" 
                    class="p-4 {{ $table->status === 'occupied' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition font-semibold">
                {{ $table->table_number }}
            </button>
            @endforeach
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-white">Payment</h2>
            <button onclick="closeModal('checkoutModal')" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="space-y-4">
            <div class="text-center py-4 bg-gray-700 rounded-lg">
                <div class="text-sm text-gray-400">Total Amount</div>
                <div class="text-3xl font-bold text-white">Rs. <span id="checkoutTotal">0.00</span></div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Payment Method</label>
                <select id="paymentMethod" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="upi">UPI</option>
                    <option value="split">Split Payment</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Amount Paid</label>
                <input type="number" id="amountPaid" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500" step="0.01">
            </div>
            
            <div class="flex justify-between text-white">
                <span>Change:</span>
                <span class="font-bold" id="changeAmount">0.00</span>
            </div>
            
            <button onclick="processPayment()" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                Complete Payment
            </button>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform transition-all scale-100">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-900/50 mb-4">
                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-white mb-2" id="notificationTitle">Notification</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-300" id="notificationMessage">Message goes here</p>
            </div>
            <div class="mt-5">
                <button onclick="closeNotification()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform transition-all scale-100">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-900/50 mb-4">
                <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-white mb-2" id="confirmationTitle">Confirm Action</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-300" id="confirmationMessage">Are you sure?</p>
            </div>
            <div class="mt-5 flex justify-center space-x-3">
                <button onclick="closeConfirmation()" class="inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm transition">
                    Cancel
                </button>
                <button id="confirmBtn" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm transition">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let billItems = [];
let currentOrderType = 'dine-in';
let selectedTableId = null;

// Add item to bill
function addItemToBill(itemId, itemName, itemPrice) {
    const existingItem = billItems.find(item => item.item_id === itemId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        billItems.push({
            item_id: itemId,
            name: itemName,
            price: itemPrice,
            quantity: 1,
            modifiers: []
        });
    }
    
    renderBill();
    calculateTotals();
}

// Render bill items
function renderBill() {
    const billItemsDiv = document.getElementById('billItems');
    
    if (billItems.length === 0) {
        billItemsDiv.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <svg class="w-16 h-16 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <p>No items added</p>
            </div>
        `;
        return;
    }
    
    billItemsDiv.innerHTML = billItems.map((item, index) => `
        <div class="bg-gray-700 rounded-lg p-3 border border-gray-600">
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div class="col-span-2">
                    <div class="font-semibold text-white">${index + 1}. ${item.name}</div>
                    <div class="text-xs text-gray-400">Rs. ${item.price.toFixed(2)} each</div>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2">
                        <button onclick="decrementQuantity(${index})" class="w-6 h-6 bg-red-600 text-white rounded hover:bg-red-700">-</button>
                        <span class="text-white font-semibold">${item.quantity}</span>
                        <button onclick="incrementQuantity(${index})" class="w-6 h-6 bg-green-600 text-white rounded hover:bg-green-700">+</button>
                    </div>
                </div>
            </div>
            <div class="text-right mt-2 text-white font-semibold">
                Rs. ${(item.price * item.quantity).toFixed(2)}
            </div>
        </div>
    `).join('');
}

// Increment/Decrement quantity
function incrementQuantity(index) {
    billItems[index].quantity++;
    renderBill();
    calculateTotals();
}

function decrementQuantity(index) {
    if (billItems[index].quantity > 1) {
        billItems[index].quantity--;
    } else {
        billItems.splice(index, 1);
    }
    renderBill();
    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    const subtotal = billItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('total').textContent = subtotal.toFixed(2);
}

// Clear bill
function clearBill() {
    showConfirmation('Are you sure you want to clear all items?', 'Clear Bill', () => {
        billItems = [];
        renderBill();
        calculateTotals();
    });
}

// Open modals
function openTableOrderModal() {
    currentOrderType = 'dine-in';
    document.getElementById('tableModal').classList.remove('hidden');
}

// Set Order Type Helper
function setOrderType(type, label) {
    currentOrderType = type;
    selectedTableId = null;
    const display = document.getElementById('orderTypeDisplay');
    if (display) display.textContent = label;
    
    // Show menu
    // Show menu
    document.getElementById('menuSelectionContainer').classList.remove('hidden');
    document.getElementById('menuSelectionContainer').classList.add('flex');
    const msg = document.getElementById('initialStateMessage');
    if (msg) msg.classList.add('hidden');
}

// Select Item (Check for Portions)
function selectItem(itemId, itemName, itemPrice, modifiers) {
    // Check for size/portion modifiers
    // Assuming 'type' field in modifiers distinguishes sizes, or we check for specific names
    // If no 'type' field exists in your data, you might need to rely on names or add a type field.
    // Based on the user request, we assume there ARE portion sizes.
    // Let's filter for modifiers that might be portions. 
    // If you don't have a 'type', we can just show all modifiers or filter by some logic.
    // For now, I'll assume any modifier is a "portion" if the user wants to select it here.
    // OR better, let's look for a 'type' == 'size' or 'portion'.
    
    const portionModifiers = modifiers.filter(m => m.type === 'size' || m.type === 'portion' || m.name.toLowerCase().includes('large') || m.name.toLowerCase().includes('small') || m.name.toLowerCase().includes('regular'));
    
    if (portionModifiers.length > 0) {
        showPortionSelection(itemId, itemName, itemPrice, portionModifiers);
    } else {
        addItemToBill(itemId, itemName, itemPrice);
    }
}

function showPortionSelection(itemId, itemName, basePrice, portions) {
    const area = document.getElementById('portionSelectionArea');
    const optionsDiv = document.getElementById('portionOptions');
    
    optionsDiv.innerHTML = portions.map(p => `
        <button class="p-3 bg-blue-700 text-white rounded-lg hover:bg-blue-600 transition font-semibold"
                onclick="addPortionToBill(${itemId}, '${itemName}', ${basePrice}, ${p.id}, '${p.name}', ${p.price_adjustment})">
            ${p.name} (+${p.price_adjustment})
        </button>
    `).join('');
    
    // Add option for base item (Regular/No Modifier) if needed? 
    // Usually if there are portions, one of them is the default or base. 
    // If the base price is the "Regular" price and portions are add-ons, we might want a "Regular" button.
    // But if portions are "Small", "Large" and they replace the price, it's different.
    // The code uses price_adjustment, so it adds to the base price.
    
    optionsDiv.innerHTML += `
        <button class="p-3 bg-gray-600 text-white rounded-lg hover:bg-gray-500 transition font-semibold"
                onclick="addItemToBill(${itemId}, '${itemName}', ${basePrice}); cancelPortionSelection();">
            Base / Regular
        </button>
    `;

    area.classList.remove('hidden');
    // Scroll to portion area
    area.scrollIntoView({ behavior: 'smooth' });
}

function cancelPortionSelection() {
    document.getElementById('portionSelectionArea').classList.add('hidden');
}

function addPortionToBill(itemId, itemName, basePrice, modifierId, modifierName, priceAdjustment) {
    const finalPrice = basePrice + priceAdjustment;
    const fullName = `${itemName} (${modifierName})`;
    
    // Add to bill with modifier tracking
    const existingItem = billItems.find(item => item.item_id === itemId && item.modifiers.some(m => m.id === modifierId));
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        billItems.push({
            item_id: itemId,
            name: fullName,
            price: finalPrice,
            quantity: 1,
            modifiers: [{id: modifierId, name: modifierName, price: priceAdjustment}]
        });
    }
    
    renderBill();
    calculateTotals();
    cancelPortionSelection();
}

function openTakeAwayModal() {
    setOrderType('take-away', 'Take Away');
}



function selectTable(tableNumber, tableId) {
    selectedTableId = tableId;
    currentOrderType = 'dine-in';
    const display = document.getElementById('orderTypeDisplay');
    if (display) display.textContent = 'Table: ' + tableNumber;
    
    // Show menu
    document.getElementById('menuSelectionContainer').classList.remove('hidden');
    document.getElementById('menuSelectionContainer').classList.add('flex');
    const msg = document.getElementById('initialStateMessage');
    if (msg) msg.classList.add('hidden');
    
    // Close the modal explicitly
    document.getElementById('tableModal').classList.add('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Checkout
function checkout() {
    if (billItems.length === 0) {
        showNotification('Please add items to the bill first', 'Empty Bill');
        return;
    }
    
    const total = document.getElementById('total').textContent;
    document.getElementById('checkoutTotal').textContent = total;
    document.getElementById('checkoutModal').classList.remove('hidden');
}

// Process payment
async function processPayment() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const total = parseFloat(document.getElementById('total').textContent);
    
    if (amountPaid < total) {
        showNotification('Amount paid is less than total amount', 'Payment Error');
        return;
    }
    
    const orderData = {
        order_type: currentOrderType,
        table_id: selectedTableId,
        items: billItems,
        payment_method: paymentMethod,
        amount_paid: amountPaid,
        _token: '{{ csrf_token() }}'
    };
    
    try {
        const response = await fetch('{{ route("pos.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Order processed successfully!', 'Success');
            billItems = [];
            renderBill();
            calculateTotals();
            closeModal('checkoutModal');
            
            // Optionally print receipt
            showConfirmation('Do you want to print the receipt?', 'Print Receipt', () => {
                window.open('/pos/receipt/' + result.order.id, '_blank');
            });
        } else {
            showNotification('Error: ' + result.message, 'Processing Error');
        }
    } catch (error) {
        showNotification('Error processing payment: ' + error.message, 'System Error');
    }
}

// Calculate change
document.getElementById('amountPaid')?.addEventListener('input', function() {
    const amountPaid = parseFloat(this.value) || 0;
    const total = parseFloat(document.getElementById('total').textContent);
    const change = Math.max(0, amountPaid - total);
    document.getElementById('changeAmount').textContent = change.toFixed(2);
});

// Filter by category
function filterByCategory(categoryId) {
    const items = document.querySelectorAll('#itemsGrid button');
    items.forEach(item => {
        if (item.dataset.category == categoryId) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });

    // Update tabs
    document.querySelectorAll('.category-tab').forEach(tab => {
        if (tab.dataset.id == categoryId) {
            tab.classList.remove('text-gray-400');
            tab.classList.add('bg-blue-600', 'text-white');
        } else {
            tab.classList.add('text-gray-400');
            tab.classList.remove('bg-blue-600', 'text-white');
        }
    });
}

// Initialize with first category
document.addEventListener('DOMContentLoaded', () => {
    const firstTab = document.querySelector('.category-tab');
    if (firstTab) {
        filterByCategory(firstTab.dataset.id);
    }
});

// Search items
document.getElementById('searchItems')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = document.querySelectorAll('#itemsGrid button');
    
    items.forEach(item => {
        const itemName = item.textContent.toLowerCase();
        if (itemName.includes(searchTerm)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
});

// Scroll functions
function scrollUp() {
    document.querySelector('#itemsGrid').parentElement.scrollBy({ top: -300, behavior: 'smooth' });
}

function scrollDown() {
    document.querySelector('#itemsGrid').parentElement.scrollBy({ top: 300, behavior: 'smooth' });
}

// Placeholder functions
function showModifiersModal() { showNotification('Modifiers feature coming soon', 'Feature Unavailable'); }
function voidItem() { showNotification('Select an item to void', 'Void Item'); }
function cancelOrder() { 
    showConfirmation('Are you sure you want to cancel the entire order?', 'Cancel Order', () => {
        // Clear items
        billItems = [];
        renderBill();
        calculateTotals();
        
        // Reset state
        currentOrderType = null;
        selectedTableId = null;
        
        // Reset UI
        const display = document.getElementById('orderTypeDisplay');
        if (display) display.textContent = 'Select Order Type';
        
        // Hide menu and show initial message
        document.getElementById('menuSelectionContainer').classList.remove('flex');
        document.getElementById('menuSelectionContainer').classList.add('hidden');
        
        const msg = document.getElementById('initialStateMessage');
        if (msg) msg.classList.remove('hidden');
        
        // Hide portion selection if open
        cancelPortionSelection();
    });
}
function splitOrder() { showNotification('Split order feature coming soon', 'Feature Unavailable'); }
function mergeOrder() { showNotification('Merge order feature coming soon', 'Feature Unavailable'); }
function transferTable() { showNotification('Table transfer feature coming soon', 'Feature Unavailable'); }
function printCopy() { showNotification('Print feature coming soon', 'Feature Unavailable'); }
function openOrderCheckModal() { showNotification('Open checks feature coming soon', 'Feature Unavailable'); }
function lockScreen() { 
    showConfirmation('Are you sure you want to lock the screen?', 'Lock Screen', () => {
        window.location.href = '{{ route("dashboard") }}';
    });
}
function switchTab(tab) { showNotification('Switching to ' + tab); }

// Notification Helper Functions
function showNotification(message, title = 'Notification') {
    document.getElementById('notificationMessage').textContent = message;
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationModal').classList.remove('hidden');
}

function closeNotification() {
    document.getElementById('notificationModal').classList.add('hidden');
}

// Confirmation Helper Functions
let confirmCallback = null;

function showConfirmation(message, title, callback) {
    document.getElementById('confirmationMessage').textContent = message;
    document.getElementById('confirmationTitle').textContent = title;
    confirmCallback = callback;
    document.getElementById('confirmationModal').classList.remove('hidden');
}

function closeConfirmation() {
    document.getElementById('confirmationModal').classList.add('hidden');
    confirmCallback = null;
}

document.getElementById('confirmBtn').addEventListener('click', () => {
    if (confirmCallback) {
        confirmCallback();
    }
    closeConfirmation();
});

// Live Clock
function updateClock() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    };
    
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    const dayName = days[now.getDay()];
    const monthName = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();
    
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const hoursStr = hours.toString().padStart(2, '0');
    
    const formattedTime = `${dayName}, ${monthName} ${date}, ${year} - ${hoursStr}:${minutes} ${ampm}`;
    
    const clockElement = document.getElementById('pos-clock');
    if (clockElement) {
        clockElement.textContent = formattedTime;
    }
}

// Update immediately and then every second
updateClock();
setInterval(updateClock, 1000);
</script>
@endsection
