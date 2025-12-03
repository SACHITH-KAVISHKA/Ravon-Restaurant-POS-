@extends('layouts.app', ['hideNavigation' => true])

@section('title', 'POS - Ravon Restaurant')

@section('content')
<div class="h-screen flex flex-col bg-gray-900">
    <!-- Top Bar -->
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Quantity
                    </button>
                    <button class="bg-gray-700 text-white py-2 rounded hover:bg-gray-600 transition flex items-center justify-center" onclick="showModifiersModal()">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Modifiers
                    </button>
                    <button class="bg-red-600 text-white py-2 rounded hover:bg-red-700 transition flex items-center justify-center" onclick="voidItem()">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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


                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Menu Items Grid (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <!-- Category Filters Removed -->

                        <!-- Search Bar -->
                        <div class="mb-4">
                            <div class="flex gap-2">
                                <input type="text" id="searchItems" placeholder="Search items..." class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Items Grid -->
                        <div class="grid grid-cols-4 gap-3" id="itemsGrid">
                            @foreach($categories as $category)
                            @foreach($category->availableItems as $item)
                            <button class="p-5 bg-gray-700 text-white rounded-lg hover:bg-blue-600 transition border border-gray-600 hover:border-blue-500 text-center flex items-center justify-center h-full"
                                onclick="selectItem({{ $item->id }}, '{{ $item->name }}', {{ $item->price }}, {{ json_encode($item->modifiers) }})"
                                data-category="{{ $category->id }}">
                                <div class="font-semibold text-lg">{{ $item->name }}</div>
                            </button>
                            @endforeach
                            @endforeach
                        </div>
                    </div>

                    <!-- Portion Selection Area (Fixed Box at Bottom - Always Visible, Content Changes) -->
                    <div id="portionSelectionArea" class="bg-gray-700 border-t-2 border-blue-500 relative" style="height: 294px; min-height: 294px; max-height: 294px;">
                        <button onclick="cancelPortionSelection()" id="closePortionBtn" class="hidden absolute top-3 right-3 text-gray-400 hover:text-white z-10">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="p-4 h-full flex flex-col">
                            <h3 class="text-lg font-bold text-white mb-4">Select Portion / Size</h3>
                            <div id="portionOptions" class="grid grid-cols-3 gap-3 flex-1 content-start overflow-y-auto">
                                <!-- Content will be dynamically updated by JavaScript -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Initial State Message -->
            <div id="initialStateMessage" class="flex-1 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-xl font-semibold">Select an Order Type to view menu</p>
                </div>
            </div>
        </div>

        <!-- Right Actions Panel (Moved Here) -->
        <div class="w-80 bg-gray-800 border-l border-gray-700 p-3 space-y-2 overflow-y-auto">
            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openTableOrderModal()">
                <div class="bg-white text-emerald-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="bg-emerald-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-emerald-600 transition">
                    Table Order
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openTakeAwayModal()">
                <div class="bg-white text-amber-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div class="bg-amber-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-amber-500 transition">
                    Take Away
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openPickMeRefModal()">
                <div class="bg-white text-pink-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <span class="font-bold text-sm">Pick</span>
                </div>
                <div class="bg-pink-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-pink-500 transition">
                    PickMe Food
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="checkout()">
                <div class="bg-white text-green-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                </div>
                <div class="bg-green-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-green-600 transition">
                    Place Order
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openOrderCheckModal()">
                <div class="bg-white text-blue-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="bg-blue-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-blue-500 transition">
                    Open Checks
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="showCloseOrderModal()">
                <div class="bg-white text-yellow-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="bg-yellow-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-yellow-500 transition">
                    Close Order
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="clearBill()">
                <div class="bg-white text-red-400 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="bg-red-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-red-500 transition">
                    Clear
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="cancelOrder()">
                <div class="bg-white text-red-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="bg-red-600 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-red-700 transition">
                    Cancel
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="splitOrder()">
                <div class="bg-white text-purple-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="bg-purple-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-purple-500 transition">
                    Split Order
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="mergeOrder()">
                <div class="bg-white text-teal-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div class="bg-teal-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-teal-500 transition">
                    Merge Order
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="transferTable()">
                <div class="bg-white text-cyan-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div class="bg-cyan-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-cyan-500 transition">
                    Table Transfer
                </div>
            </button>

            <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openClosedOrdersModal()">
                <div class="bg-white text-rose-400 p-3 rounded-l-lg flex items-center justify-center w-14">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-5 gap-4" id="tableGrid">
                    <!-- Tables will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- PickMe Reference Number Modal -->
        <div id="pickMeRefModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
            <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-white">PickMe Food Order</h2>
                    <button onclick="closeModal('pickMeRefModal')" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            Reference Number *
                        </label>
                        <input
                            type="text"
                            id="pickMeRefNumber"
                            placeholder="Enter PickMe reference number"
                            class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-pink-500 text-lg font-semibold"
                            autocomplete="off"
                            onkeypress="if(event.key === 'Enter') confirmPickMeRef()">
                    </div>

                    <button
                        onclick="confirmPickMeRef()"
                        class="w-full px-4 py-3 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition font-bold text-lg flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Continue to Order</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Open Checks Modal -->
        <div id="openChecksModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
            <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-white">Open Checks</h2>
                    <button onclick="closeModal('openChecksModal')" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="openChecksContainer" class="space-y-3 max-h-96 overflow-y-auto">
                    <!-- Open orders will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Close Order Payment Modal -->
        <div id="closeOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
            <div class="bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full mx-4" style="max-height: 90vh; overflow-y: auto;">
                <!-- Header -->
                <div class="flex justify-between items-center p-6 border-b border-gray-700 bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h2 class="text-2xl font-bold text-white">Payment Processing</h2>
                    </div>
                    <button onclick="closeModal('closeOrderModal')" class="text-white hover:text-gray-200 transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Side - Payment Controls -->
                    <div class="space-y-4">
                        <!-- Payment Type Selection -->
                        <div>
                            <label class="block text-sm font-bold text-gray-300 mb-3">SELECT PAYMENT TYPE</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button onclick="selectPaymentType('cash')" id="paymentTypeCash" class="payment-type-btn bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                    CASH
                                </button>
                                <button onclick="selectPaymentType('card')" id="paymentTypeCard" class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                    CARD
                                </button>
                                <button onclick="selectPaymentType('card_cash')" id="paymentTypeCardCash" class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                    CARD & CASH
                                </button>
                                <button onclick="selectPaymentType('credit')" id="paymentTypeCredit" class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                    CREDIT
                                </button>
                            </div>
                        </div>

                        <!-- Number Pad -->
                        <div class="bg-gray-700 p-4 rounded-lg">
                            <div class="grid grid-cols-3 gap-2">
                                <button onclick="appendNumber('7')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">7</button>
                                <button onclick="appendNumber('8')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">8</button>
                                <button onclick="appendNumber('9')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">9</button>

                                <button onclick="appendNumber('4')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">4</button>
                                <button onclick="appendNumber('5')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">5</button>
                                <button onclick="appendNumber('6')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">6</button>

                                <button onclick="appendNumber('1')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">1</button>
                                <button onclick="appendNumber('2')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">2</button>
                                <button onclick="appendNumber('3')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">3</button>

                                <button onclick="appendNumber('0')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">0</button>
                                <button onclick="appendNumber('.')" class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">.</button>
                                <button onclick="backspaceNumber()" class="numberpad-btn bg-yellow-500 hover:bg-yellow-600 text-white font-bold text-xl py-4 rounded-lg transition">
                                    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                                    </svg>
                                </button>

                                <button onclick="clearNumber()" class="numberpad-btn bg-red-600 hover:bg-red-700 text-white font-bold text-xl py-4 rounded-lg transition col-span-3">C</button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Payment Summary -->
                    <div class="space-y-4">
                        <div class="bg-gray-700 rounded-lg p-4 space-y-3">
                            <!-- Sub Total -->
                            <div class="flex justify-between items-center text-gray-300">
                                <span class="font-semibold">Sub Total</span>
                                <span class="font-bold" id="paymentSubtotal">0.00</span>
                            </div>

                            <!-- Total -->
                            <div class="flex justify-between items-center py-2 border-t border-gray-600">
                                <span class="font-bold text-blue-400 text-lg">Total ——→</span>
                                <span class="font-bold text-blue-400 text-2xl" id="paymentTotal">0.00</span>
                            </div>

                            <!-- Card Amount -->
                            <div class="flex justify-between items-center text-gray-300" id="cardAmountRow" style="display: none;">
                                <span>Card</span>
                                <span id="paymentCardAmount">0.00</span>
                            </div>

                            <!-- Cash Amount Input -->
                            <div>
                                <label class="block text-sm text-gray-400 mb-2">Cash Amount</label>
                                <input type="text" id="paymentCashInput" readonly class="w-full px-4 py-3 bg-yellow-50 text-gray-900 rounded-lg font-bold text-xl text-right border-2 border-yellow-400 focus:outline-none" value="0.00">
                            </div>

                            <!-- Balance -->
                            <div class="flex justify-between items-center py-2 border-t border-gray-600" id="balanceRow">
                                <span class="font-bold text-green-400">Balance ——→</span>
                                <span class="font-bold text-green-400 text-xl" id="paymentBalance">0.00</span>
                            </div>

                            <!-- Credit -->
                            <div class="flex justify-between items-center bg-red-50 rounded p-2" id="creditRow" style="display: none;">
                                <span class="font-bold text-red-600">Credit ——→</span>
                                <span class="font-bold text-red-600 text-xl" id="paymentCredit">0.00</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button onclick="closeModal('closeOrderModal')" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg font-bold transition flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>Back</span>
                            </button>
                            <button onclick="completePayment()" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Print Receipt</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Closed Orders Modal -->
        <div id="closedOrdersModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
            <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4" style="max-height: 90vh; overflow-y: auto;">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-white">Closed Orders / Bills</h2>
                    <button onclick="closeModal('closedOrdersModal')" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="closedOrdersContainer" class="space-y-3 max-h-96 overflow-y-auto">
                    <!-- Closed orders will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Checkout Modal (OLD - Keep for compatibility) -->
        <div id="checkoutModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
            <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-white">Payment</h2>
                    <button onclick="closeModal('checkoutModal')" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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
            // Global variables
            let billItems = [];
            let currentOrderType = null;
            let selectedTableId = null;
            let currentOrderId = null; // Track current order for updates
            let pickMeRefNumber = null; // Store PickMe reference number

            // Add item to bill
            function addItemToBill(itemId, itemName, itemPrice) {
                const existingItem = billItems.find(item => item.item_id === itemId && item.name === itemName);

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
                    pickMeRefNumber = null; // Reset PickMe reference number
                    renderBill();
                    calculateTotals();
                });
            }

            // Set Order Type Helper
            function setOrderType(type, label) {
                currentOrderType = type;
                selectedTableId = null;
                currentOrderId = null;
                const display = document.getElementById('orderTypeDisplay');
                if (display) display.textContent = label;

                document.getElementById('menuSelectionContainer').classList.remove('hidden');
                document.getElementById('menuSelectionContainer').classList.add('flex');
                const msg = document.getElementById('initialStateMessage');
                if (msg) msg.classList.add('hidden');
            }

            // Select Item (Check for Sub-items/Portions)
            function selectItem(itemId, itemName, itemPrice, modifiers) {
                const portionModifiers = modifiers.filter(m => {
                    const name = m.name.toLowerCase();
                    const type = (m.type || '').toLowerCase();

                    return type === 'size' || type === 'portion' ||
                        name.includes('large') || name.includes('small') || name.includes('regular') ||
                        name.includes('ml') || name.includes('liter') || name.includes(' l');
                });

                if (portionModifiers.length > 0) {
                    showPortionSelection(itemId, itemName, itemPrice, portionModifiers);
                } else {
                    addItemToBill(itemId, itemName, itemPrice);
                    clearPortionSelection();
                }
            }

            function showPortionSelection(itemId, itemName, basePrice, portions) {
                const optionsDiv = document.getElementById('portionOptions');
                const closeBtn = document.getElementById('closePortionBtn');

                optionsDiv.innerHTML = portions.map(p => `
                    <button class="p-3 bg-blue-700 text-white rounded-lg hover:bg-blue-600 transition font-semibold"
                            onclick="addPortionToBill(${itemId}, '${itemName}', ${p.price_adjustment}, '${p.name}')">
                        ${p.name}
                    </button>
                `).join('');

                closeBtn.classList.remove('hidden');
            }

            function cancelPortionSelection() {
                clearPortionSelection();
            }

            function clearPortionSelection() {
                const optionsDiv = document.getElementById('portionOptions');
                const closeBtn = document.getElementById('closePortionBtn');
                optionsDiv.innerHTML = '';
                closeBtn.classList.add('hidden');
            }

            function addPortionToBill(itemId, itemName, portionPrice, portionName) {
                const fullName = `${itemName} (${portionName})`;
                const existingItem = billItems.find(item => item.item_id === itemId && item.name === fullName);

                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    billItems.push({
                        item_id: itemId,
                        name: fullName,
                        price: portionPrice,
                        quantity: 1,
                        modifiers: []
                    });
                }

                renderBill();
                calculateTotals();
                clearPortionSelection();
            }

            function openTakeAwayModal() {
                setOrderType('takeaway', 'Take Away');
            }

            // PickMe Food Modal Functions
            function openPickMeRefModal() {
                document.getElementById('pickMeRefModal').classList.remove('hidden');
                document.getElementById('pickMeRefNumber').value = '';
                // Focus on input field
                setTimeout(() => {
                    document.getElementById('pickMeRefNumber').focus();
                }, 100);
            }

            function confirmPickMeRef() {
                const refNumber = document.getElementById('pickMeRefNumber').value.trim();

                if (!refNumber) {
                    showNotification('Please enter a reference number', 'Reference Required');
                    return;
                }

                // Store the reference number
                pickMeRefNumber = refNumber;

                // Close modal
                closeModal('pickMeRefModal');

                // Set order type and show menu
                setOrderType('pickme', 'PickMe Food - Ref: ' + refNumber);
            }

            function selectTable(tableNumber, tableId) {
                selectedTableId = tableId;
                currentOrderType = 'dine_in';
                currentOrderId = null;
                const display = document.getElementById('orderTypeDisplay');
                if (display) display.textContent = 'Table: ' + tableNumber;

                document.getElementById('menuSelectionContainer').classList.remove('hidden');
                document.getElementById('menuSelectionContainer').classList.add('flex');
                const msg = document.getElementById('initialStateMessage');
                if (msg) msg.classList.add('hidden');

                document.getElementById('tableModal').classList.add('hidden');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
            }

            // Checkout - Place Order
            async function checkout() {
                if (billItems.length === 0) {
                    showNotification('Please add items to the bill first', 'Empty Bill');
                    return;
                }

                if (!currentOrderType) {
                    showNotification('Please select an order type first', 'Order Type Required');
                    return;
                }

                try {
                    // Merge duplicate items before sending
                    const mergedItems = {};
                    billItems.forEach(item => {
                        const key = item.item_id + '_' + item.name;
                        if (mergedItems[key]) {
                            // Item exists, add quantities
                            mergedItems[key].quantity += item.quantity;
                        } else {
                            // New item, add to merged list
                            mergedItems[key] = {
                                item_id: item.item_id,
                                name: item.name,
                                price: item.price,
                                quantity: item.quantity
                            };
                        }
                    });

                    // Convert merged items object to array
                    const itemsToSend = Object.values(mergedItems);

                    const orderData = {
                        order_id: currentOrderId,
                        order_type: currentOrderType,
                        table_id: selectedTableId,
                        items: itemsToSend,
                        pickme_ref_number: pickMeRefNumber // Include PickMe reference if available
                    };

                    console.log('Sending order data:', orderData);

                    const response = await fetch('{{ route("pos.placeOrder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(orderData)
                    });

                    console.log('Response status:', response.status);
                    const responseText = await response.text();
                    console.log('Response text:', responseText);

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        console.error('Response was:', responseText.substring(0, 500));
                        showNotification('Server error. Check console for details.', 'Error');
                        return;
                    }

                    if (result.success) {
                        showNotification(result.message + ' (KOT/BOT sent to kitchen/bar)', 'Success');

                        // Clear the POS for next order
                        billItems = [];
                        currentOrderId = null;
                        currentOrderType = null;
                        selectedTableId = null;

                        // Reset UI
                        renderBill();
                        calculateTotals();

                        const display = document.getElementById('orderTypeDisplay');
                        if (display) display.textContent = 'Select Order Type';

                        // Hide menu and show initial message
                        document.getElementById('menuSelectionContainer').classList.remove('flex');
                        document.getElementById('menuSelectionContainer').classList.add('hidden');

                        const msg = document.getElementById('initialStateMessage');
                        if (msg) msg.classList.remove('hidden');

                        // Hide portion selection if open
                        cancelPortionSelection();
                    } else {
                        showNotification('Error: ' + (result.message || 'Unknown error'), 'Order Error');
                    }
                } catch (error) {
                    console.error('Checkout error:', error);
                    showNotification('Error placing order: ' + error.message, 'System Error');
                }
            }

            // Open Table Selection Modal
            async function openTableOrderModal() {
                try {
                    const response = await fetch('{{ route("pos.tables") }}');
                    const result = await response.json();

                    if (result.success) {
                        const tableGrid = document.getElementById('tableGrid');
                        tableGrid.innerHTML = result.tables.map(table => {
                            let bgColor = 'bg-green-600 hover:bg-green-700';
                            let clickable = true;

                            if (!table.is_available) {
                                bgColor = 'bg-red-600 cursor-not-allowed opacity-60';
                                clickable = false;
                            }

                            return `
                                <button 
                                    ${clickable ? `onclick="selectTable('${table.table_number}', ${table.id})"` : 'disabled'}
                                    class="p-4 ${bgColor} text-white rounded-lg transition font-semibold">
                                    ${table.table_number}
                                    ${!table.is_available ? '<br><span class="text-xs">(Reserved)</span>' : ''}
                                </button>
                            `;
                        }).join('');

                        document.getElementById('tableModal').classList.remove('hidden');
                    }
                } catch (error) {
                    showNotification('Error loading tables: ' + error.message, 'Error');
                }
            }

            // Open Open Checks Modal
            async function openOrderCheckModal() {
                try {
                    const response = await fetch('{{ route("pos.openChecks") }}');
                    const result = await response.json();

                    if (result.success) {
                        const container = document.getElementById('openChecksContainer');

                        if (result.orders.length === 0) {
                            container.innerHTML = `
                                <div class="text-center text-gray-500 py-8">
                                    <p>No open checks</p>
                                </div>
                            `;
                        } else {
                            container.innerHTML = result.orders.map(order => `
                                <div class="bg-gray-700 rounded-lg p-4 hover:bg-gray-600 cursor-pointer transition"
                                     onclick="loadOrder(${order.id})">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="text-white font-semibold">${order.order_number}</div>
                                            <div class="text-sm text-gray-400">
                                                Table: ${order.table_number} | ${order.items_count} items
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-white font-bold">Rs. ${parseFloat(order.total_amount).toFixed(2)}</div>
                                            <div class="text-xs text-gray-400">${order.created_at}</div>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        }

                        document.getElementById('openChecksModal').classList.remove('hidden');
                    }
                } catch (error) {
                    showNotification('Error loading open checks: ' + error.message, 'Error');
                }
            }

            // Load existing order
            async function loadOrder(orderId) {
                try {
                    const response = await fetch(`{{ url('/pos/order') }}/${orderId}`);
                    const result = await response.json();

                    if (result.success) {
                        closeModal('openChecksModal');

                        // Set order details
                        currentOrderId = orderId;
                        currentOrderType = result.order.order_type;
                        selectedTableId = result.order.table_id;

                        // Update display
                        const display = document.getElementById('orderTypeDisplay');
                        if (result.order.table_number) {
                            display.textContent = 'Table: ' + result.order.table_number;
                        } else {
                            // Format order type for display
                            const orderTypeLabels = {
                                'takeaway': 'Take Away',
                                'delivery': 'Delivery',
                                'pickme': 'PickMe Food',
                                'dine_in': 'Dine In'
                            };
                            display.textContent = orderTypeLabels[result.order.order_type] || result.order.order_type;
                        }

                        // Load items - merge duplicates if any exist in database
                        const mergedItems = {};
                        result.order.items.forEach(item => {
                            const key = item.item_id + '_' + item.name;
                            if (mergedItems[key]) {
                                // Duplicate found - merge quantities
                                mergedItems[key].quantity += parseInt(item.quantity);
                            } else {
                                // New item
                                mergedItems[key] = {
                                    item_id: item.item_id,
                                    name: item.name,
                                    price: parseFloat(item.price),
                                    quantity: parseInt(item.quantity),
                                    modifiers: item.modifiers || []
                                };
                            }
                        });

                        // Convert to array
                        billItems = Object.values(mergedItems);

                        renderBill();
                        calculateTotals();

                        // Show menu section
                        document.getElementById('menuSelectionContainer').classList.remove('hidden');
                        document.getElementById('menuSelectionContainer').classList.add('flex');
                        const msg = document.getElementById('initialStateMessage');
                        if (msg) msg.classList.add('hidden');

                        showNotification('Order #' + result.order.order_number + ' loaded. You can add more items or close the order.', 'Order Loaded');
                    }
                } catch (error) {
                    showNotification('Error loading order: ' + error.message, 'Error');
                }
            }

            // Show Close Order Modal
            function showCloseOrderModal() {
                if (!currentOrderId) {
                    showNotification('No active order to close', 'No Order');
                    return;
                }

                const total = document.getElementById('total').textContent;
                document.getElementById('closeOrderTotal').textContent = total;
                document.getElementById('closeOrderModal').classList.remove('hidden');
            }

            // Calculate change for close order
            document.getElementById('closeOrderAmountPaid')?.addEventListener('input', function() {
                const amountPaid = parseFloat(this.value) || 0;
                const total = parseFloat(document.getElementById('total').textContent);
                const change = Math.max(0, amountPaid - total);
                document.getElementById('closeOrderChange').textContent = change.toFixed(2);
            });

            // Complete Payment
            async function completePayment() {
                const paymentMethod = document.getElementById('closeOrderPaymentMethod').value;
                const amountPaid = parseFloat(document.getElementById('closeOrderAmountPaid').value) || 0;
                const total = parseFloat(document.getElementById('total').textContent);

                if (amountPaid < total) {
                    showNotification('Amount paid is less than total amount', 'Payment Error');
                    return;
                }

                if (!currentOrderId) {
                    showNotification('No active order to close', 'Error');
                    return;
                }

                try {
                    const response = await fetch('{{ route("pos.payment") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            order_id: currentOrderId,
                            payment_method: paymentMethod,
                            amount_paid: amountPaid
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showNotification('Payment completed successfully!', 'Success');

                        billItems = [];
                        currentOrderId = null;
                        currentOrderType = null;
                        selectedTableId = null;

                        renderBill();
                        calculateTotals();
                        closeModal('closeOrderModal');

                        const display = document.getElementById('orderTypeDisplay');
                        if (display) display.textContent = 'Select Order Type';

                        document.getElementById('menuSelectionContainer').classList.remove('flex');
                        document.getElementById('menuSelectionContainer').classList.add('hidden');
                        const msg = document.getElementById('initialStateMessage');
                        if (msg) msg.classList.remove('hidden');

                        showConfirmation('Do you want to print the receipt?', 'Print Receipt', () => {
                            window.open('/pos/receipt/' + result.order.id, '_blank');
                        });
                    } else {
                        showNotification('Error: ' + result.message, 'Payment Error');
                    }
                } catch (error) {
                    showNotification('Error processing payment: ' + error.message, 'System Error');
                }
            }

            function cancelOrder() {
                showConfirmation('Are you sure you want to cancel the entire order?', 'Cancel Order', () => {
                    billItems = [];
                    renderBill();
                    calculateTotals();

                    currentOrderType = null;
                    selectedTableId = null;

                    const display = document.getElementById('orderTypeDisplay');
                    if (display) display.textContent = 'Select Order Type';

                    document.getElementById('menuSelectionContainer').classList.remove('flex');
                    document.getElementById('menuSelectionContainer').classList.add('hidden');

                    const msg = document.getElementById('initialStateMessage');
                    if (msg) msg.classList.remove('hidden');

                    cancelPortionSelection();
                });
            }

            function splitOrder() {
                showNotification('Split order feature coming soon', 'Feature Unavailable');
            }

            function mergeOrder() {
                showNotification('Merge order feature coming soon', 'Feature Unavailable');
            }

            function transferTable() {
                showNotification('Table transfer feature coming soon', 'Feature Unavailable');
            }

            function printCopy() {
                showNotification('Print feature coming soon', 'Feature Unavailable');
            }

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
                document.querySelector('#itemsGrid').parentElement.scrollBy({
                    top: -300,
                    behavior: 'smooth'
                });
            }

            function scrollDown() {
                document.querySelector('#itemsGrid').parentElement.scrollBy({
                    top: 300,
                    behavior: 'smooth'
                });
            }

            // Placeholder functions
            function showModifiersModal() {
                showNotification('Modifiers feature coming soon', 'Feature Unavailable');
            }

            function voidItem() {
                showNotification('Select an item to void', 'Void Item');
            }

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

            function splitOrder() {
                showNotification('Split order feature coming soon', 'Feature Unavailable');
            }

            function mergeOrder() {
                showNotification('Merge order feature coming soon', 'Feature Unavailable');
            }

            function transferTable() {
                showNotification('Table transfer feature coming soon', 'Feature Unavailable');
            }

            function printCopy() {
                showNotification('Print feature coming soon', 'Feature Unavailable');
            }


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

            // Placeholder functions for future features
            function showModifiersModal() {
                showNotification('Modifiers feature coming soon', 'Feature Unavailable');
            }

            function voidItem() {
                showNotification('Select an item to void', 'Void Item');
            }

            function lockScreen() {
                showConfirmation('Are you sure you want to lock the screen?', 'Lock Screen', () => {
                    window.location.href = '{{ route("dashboard") }}';
                });
            }


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

            // Payment Modal Variables
            let selectedPaymentType = 'cash';
            let cashInputValue = '0';

            // Show Close Order Modal
            window.showCloseOrderModal = function() {
                if (!currentOrderId) {
                    showNotification('No active order to close', 'No Order');
                    return;
                }
                selectedPaymentType = 'cash';
                cashInputValue = '0';
                const total = parseFloat(document.getElementById('total').textContent);
                document.getElementById('paymentSubtotal').textContent = total.toFixed(2);
                document.getElementById('paymentTotal').textContent = total.toFixed(2);
                document.getElementById('paymentCashInput').value = '0.00';
                selectPaymentType('cash');
                updatePaymentCalculations();
                document.getElementById('closeOrderModal').classList.remove('hidden');
            };

            // Select Payment Type
            window.selectPaymentType = function(type) {
                selectedPaymentType = type;
                document.querySelectorAll('.payment-type-btn').forEach(btn => {
                    btn.classList.remove('bg-blue-600');
                    btn.classList.add('bg-gray-700');
                });
                const btnMap = {
                    'cash': 'paymentTypeCash',
                    'card': 'paymentTypeCard',
                    'card_cash': 'paymentTypeCardCash',
                    'credit': 'paymentTypeCredit'
                };
                const selectedBtn = document.getElementById(btnMap[type]);
                if (selectedBtn) {
                    selectedBtn.classList.remove('bg-gray-700');
                    selectedBtn.classList.add('bg-blue-600');
                }
                cashInputValue = '0';
                document.getElementById('paymentCashInput').value = '0.00';
                updatePaymentCalculations();
            };

            // Number Pad
            window.appendNumber = function(num) {
                if (cashInputValue === '0' && num !== '.') cashInputValue = num;
                else if (num === '.' && cashInputValue.includes('.')) return;
                else cashInputValue += num;
                document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
                updatePaymentCalculations();
            };

            window.backspaceNumber = function() {
                cashInputValue = cashInputValue.length > 1 ? cashInputValue.slice(0, -1) : '0';
                document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
                updatePaymentCalculations();
            };

            window.clearNumber = function() {
                cashInputValue = '0';
                document.getElementById('paymentCashInput').value = '0.00';
                updatePaymentCalculations();
            };

            // Update Calculations
            function updatePaymentCalculations() {
                const total = parseFloat(document.getElementById('paymentTotal').textContent);
                const cashAmount = parseFloat(cashInputValue) || 0;
                let balance = 0,
                    credit = 0;

                if (selectedPaymentType === 'cash') {
                    balance = cashAmount - total;
                    if (balance < 0) {
                        credit = Math.abs(balance);
                        balance = 0;
                    }
                } else if (selectedPaymentType === 'credit') {
                    credit = total;
                }

                document.getElementById('paymentBalance').textContent = balance.toFixed(2);
                document.getElementById('paymentCredit').textContent = credit.toFixed(2);
                document.getElementById('creditRow').style.display = credit > 0 ? 'flex' : 'none';
                document.getElementById('balanceRow').style.display = credit > 0 ? 'none' : 'flex';
            }

            // Complete Payment
            window.completePayment = async function() {
                const total = parseFloat(document.getElementById('paymentTotal').textContent);
                const cashAmount = parseFloat(cashInputValue) || 0;

                if (selectedPaymentType === 'cash' && cashAmount < total) {
                    showNotification('Insufficient cash amount', 'Payment Error');
                    return;
                }

                if (!currentOrderId) {
                    showNotification('No active order', 'Error');
                    return;
                }

                try {
                    const response = await fetch('{{ route("pos.payment") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            order_id: currentOrderId,
                            payment_method: selectedPaymentType,
                            amount_paid: selectedPaymentType === 'card' ? total : cashAmount
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification('Payment completed!', 'Success');
                        billItems = [];
                        currentOrderId = null;
                        currentOrderType = null;
                        selectedTableId = null;
                        renderBill();
                        calculateTotals();
                        closeModal('closeOrderModal');
                        document.getElementById('orderTypeDisplay').textContent = 'Select Order Type';
                        document.getElementById('menuSelectionContainer').classList.replace('flex', 'hidden');
                        document.getElementById('initialStateMessage')?.classList.remove('hidden');

                        // Open Closed Orders modal after successful payment
                        setTimeout(() => openClosedOrdersModal(), 500);
                    } else {
                        showNotification('Error: ' + (result.message || 'Unknown error'), 'Payment Error');
                    }
                } catch (error) {
                    showNotification('Error: ' + error.message, 'System Error');
                }
            };

            // Open Closed Orders Modal
            window.openClosedOrdersModal = async function() {
                try {
                    const response = await fetch('{{ route("pos.closedOrders") }}');
                    const result = await response.json();

                    if (result.success) {
                        const container = document.getElementById('closedOrdersContainer');

                        if (result.orders.length === 0) {
                            container.innerHTML = `
                                <div class="text-center text-gray-500 py-8">
                                    <p>No closed orders</p>
                                </div>
                            `;
                        } else {
                            container.innerHTML = result.orders.map(order => `
                                <div class="bg-gray-700 rounded-lg p-4 mb-2 flex justify-between items-center hover:bg-gray-650 transition">
                                    <div>
                                        <div class="text-white font-semibold">${order.order_number}</div>
                                        <div class="text-sm text-gray-400">
                                            Table: ${order.table_number} | ${order.items_count} items | ${order.payment_method.toUpperCase()}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <div class="text-white font-bold">Rs. ${parseFloat(order.total_amount).toFixed(2)}</div>
                                            <div class="text-xs text-gray-400">${order.completed_at}</div>
                                        </div>
                                        <button onclick="printReceipt(${order.id})" 
                                                class="bg-rose-500 hover:bg-rose-600 text-white p-2 rounded-lg transition shadow-sm"
                                                title="Print Receipt">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            `).join('');
                        }

                        document.getElementById('closedOrdersModal').classList.remove('hidden');
                    }
                } catch (error) {
                    showNotification('Error loading closed orders: ' + error.message, 'Error');
                }
            };

            // Print Receipt (Hidden Iframe)
            window.printReceipt = function(orderId) {
                const url = '{{ url("/pos/receipt") }}/' + orderId;

                // Create invisible iframe
                const iframe = document.createElement('iframe');
                iframe.style.position = 'absolute';
                iframe.style.width = '0px';
                iframe.style.height = '0px';
                iframe.style.border = 'none';
                iframe.src = url;

                document.body.appendChild(iframe);

                // Remove iframe after a delay
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 10000);
            };
        </script>
        @endsection