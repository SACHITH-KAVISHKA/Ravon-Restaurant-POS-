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
                    <div class="flex justify-between text-lg font-bold text-white">
                        <span>Total</span>
                        <span id="total">0.00</span>
                    </div>
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
                            <div id="cashInputGroup">
                                <label class="block text-sm text-gray-400 mb-2">Cash Amount</label>
                                <input type="text"
                                    id="paymentCashInput"
                                    onclick="setActivePaymentInput('cash')"
                                    oninput="handlePaymentInputChange('cash')"
                                    onkeypress="return validatePaymentInput(event)"
                                    class="w-full px-4 py-3 bg-yellow-50 text-gray-900 rounded-lg font-bold text-xl text-right border-2 border-yellow-400 focus:outline-none focus:border-yellow-500"
                                    value="0.00">
                            </div>

                            <!-- Card Amount Input -->
                            <div id="cardInputGroup" class="mt-3" style="display: none;">
                                <label class="block text-sm text-gray-400 mb-2">Card Amount</label>
                                <input type="text"
                                    id="paymentCardInput"
                                    onclick="setActivePaymentInput('card')"
                                    oninput="handlePaymentInputChange('card')"
                                    onkeypress="return validatePaymentInput(event)"
                                    class="w-full px-4 py-3 bg-blue-50 text-gray-900 rounded-lg font-bold text-xl text-right border-2 border-blue-400 focus:outline-none focus:border-blue-500"
                                    value="0.00">
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

        {{-- QZ Tray for Thermal Printing --}}
        <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.js"></script>
        {{-- jsPDF for PDF generation --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

        <script>
            // Global variables
            let billItems = [];
            let currentOrderType = null;
            let selectedTableId = null;
            let currentOrderId = null; // Track current order for updates
            let pickMeRefNumber = null; // Store PickMe reference number

            // Track items that have already been sent to kitchen/bar
            let printedItems = []; // Stores items that have been printed with their quantities

            // Payment Modal Variables
            let selectedPaymentMethod = null;
            let paymentCashAmount = 0;
            let paymentCardAmount = 0;
            let activePaymentInput = 'cash'; // 'cash' or 'card'

            // Show Close Order Modal (Payment Modal)
            function showCloseOrderModal() {
                console.log('===== SHOW CLOSE ORDER MODAL =====');
                console.log('Bill Items:', billItems);
                console.log('Bill Items Length:', billItems.length);

                if (billItems.length === 0) {
                    alert('Please add items to bill before payment');
                    console.log('❌ No items in bill - aborting');
                    return;
                }

                console.log('✓ Items in bill, proceeding...');

                // Reset payment state
                selectedPaymentMethod = null;
                paymentCashAmount = 0;
                paymentCardAmount = 0;
                activePaymentInput = 'cash';

                console.log('Reset payment variables');

                // Reset UI
                const cashInput = document.getElementById('paymentCashInput');
                const cardInput = document.getElementById('paymentCardInput');
                const cashGroup = document.getElementById('cashInputGroup');
                const cardGroup = document.getElementById('cardInputGroup');
                const cardRow = document.getElementById('cardAmountRow');

                console.log('Elements found:');
                console.log('  - cashInput:', cashInput);
                console.log('  - cardInput:', cardInput);
                console.log('  - cashGroup:', cashGroup);
                console.log('  - cardGroup:', cardGroup);
                console.log('  - cardRow:', cardRow);

                if (cashInput) cashInput.value = '0.00';
                if (cardInput) cardInput.value = '0.00';
                if (cashGroup) cashGroup.style.display = 'block';
                if (cardGroup) cardGroup.style.display = 'none';
                if (cardRow) cardRow.style.display = 'none';

                console.log('Reset input values and visibility');

                // Reset payment type buttons
                const buttons = document.querySelectorAll('.payment-type-btn');
                console.log('Found', buttons.length, 'payment type buttons');

                buttons.forEach(btn => {
                    btn.classList.remove('bg-blue-600', 'border-blue-500');
                    btn.classList.add('bg-gray-700');
                });

                console.log('Reset button styles');

                // Update totals
                updatePaymentTotals();
                console.log('Updated payment totals');

                // Show modal
                const modal = document.getElementById('closeOrderModal');
                console.log('Modal element:', modal);

                if (modal) {
                    modal.classList.remove('hidden');
                    console.log('✓ Modal shown');
                } else {
                    console.error('❌ Modal element not found!');
                }

                // Auto-select CASH
                console.log('Auto-selecting CASH in 100ms...');
                setTimeout(() => {
                    console.log('Executing auto-select CASH');
                    selectPaymentType('cash');
                }, 100);

                console.log('===== END SHOW CLOSE ORDER MODAL =====');
            }

            // Select Payment Type
            function selectPaymentType(type) {
                selectedPaymentMethod = type;
                console.log('===== SELECTING PAYMENT TYPE =====');
                console.log('Type:', type);

                // Reset all buttons
                document.querySelectorAll('.payment-type-btn').forEach(btn => {
                    btn.classList.remove('bg-blue-600', 'border-blue-500');
                    btn.classList.add('bg-gray-700');
                });

                // Highlight selected button - fix ID mapping
                let btnId;
                if (type === 'cash') {
                    btnId = 'paymentTypeCash';
                } else if (type === 'card') {
                    btnId = 'paymentTypeCard';
                } else if (type === 'card_cash') {
                    btnId = 'paymentTypeCardCash';
                } else if (type === 'credit') {
                    btnId = 'paymentTypeCredit';
                }

                const button = document.getElementById(btnId);
                if (button) {
                    button.classList.remove('bg-gray-700');
                    button.classList.add('bg-blue-600', 'border-blue-500');
                    console.log('Button highlighted:', btnId);
                } else {
                    console.error('Button not found:', btnId);
                }

                // Reset amounts
                paymentCashAmount = 0;
                paymentCardAmount = 0;
                document.getElementById('paymentCashInput').value = '0.00';
                document.getElementById('paymentCardInput').value = '0.00';

                // Reset border styles
                document.getElementById('paymentCashInput').style.borderColor = '#FBBF24';
                document.getElementById('paymentCashInput').style.borderWidth = '2px';
                document.getElementById('paymentCardInput').style.borderColor = '#60A5FA';
                document.getElementById('paymentCardInput').style.borderWidth = '2px';

                // Get input elements
                const cashInputGroup = document.getElementById('cashInputGroup');
                const cardInputGroup = document.getElementById('cardInputGroup');
                const cardAmountRow = document.getElementById('cardAmountRow');

                console.log('Cash Input Group:', cashInputGroup);
                console.log('Card Input Group:', cardInputGroup);
                console.log('Card Amount Row:', cardAmountRow);

                // EXPLICITLY show/hide based on payment method
                if (type === 'cash') {
                    // CASH: Show only cash input
                    cashInputGroup.style.display = 'block';
                    cardInputGroup.style.display = 'none';
                    if (cardAmountRow) cardAmountRow.style.display = 'none';
                    activePaymentInput = 'cash';
                    console.log('✓ CASH mode: Cash input shown, Card input hidden');

                } else if (type === 'card') {
                    // CARD: Show only card input, auto-fill with total
                    cashInputGroup.style.display = 'none';
                    cardInputGroup.style.display = 'block';

                    if (cardAmountRow) {
                        cardAmountRow.style.display = 'flex';
                    }
                    activePaymentInput = 'card';

                    // Auto-fill card amount with total (EDITABLE)
                    const total = calculateTotal();
                    paymentCardAmount = total;
                    document.getElementById('paymentCardInput').value = total.toFixed(2);
                    console.log('✓ CARD mode: Card input shown with auto-fill:', total);

                } else if (type === 'card_cash') {
                    // CARD & CASH: Show BOTH inputs
                    console.log('>>> CARD & CASH: Showing BOTH fields <<<');

                    // Show BOTH inputs with display block
                    cashInputGroup.style.display = 'block';
                    cardInputGroup.style.display = 'block';

                    // Also show card amount row in summary
                    if (cardAmountRow) {
                        cardAmountRow.style.display = 'flex';
                    }

                    // Highlight selected button
                    document.getElementById("paymentTypeCardCash").classList.add("bg-blue-600");

                    activePaymentInput = 'cash';

                    // Highlight cash input as active (thicker blue border)
                    document.getElementById('paymentCashInput').style.borderColor = '#3B82F6';
                    document.getElementById('paymentCashInput').style.borderWidth = '3px';

                    console.log('✓ CARD & CASH mode: BOTH inputs shown');
                    console.log('  - Cash Input Display:', cashInputGroup.style.display);
                    console.log('  - Card Input Display:', cardInputGroup.style.display);

                } else if (type === 'credit') {
                    // CREDIT: Hide all inputs
                    cashInputGroup.style.display = 'none';
                    cardInputGroup.style.display = 'none';
                    if (cardAmountRow) cardAmountRow.style.display = 'none';
                    console.log('✓ CREDIT mode: All inputs hidden');
                }

                console.log('Final states:');
                console.log('  Cash Group display:', cashInputGroup.style.display);
                console.log('  Card Group display:', cardInputGroup.style.display);
                console.log('===== END PAYMENT TYPE SELECTION =====');

                updatePaymentTotals();
            }

            // Set Active Payment Input (for CARD & CASH mode)
            function setActivePaymentInput(inputType) {
                if (selectedPaymentMethod !== 'card_cash') return;

                activePaymentInput = inputType;

                const cashInput = document.getElementById('paymentCashInput');
                const cardInput = document.getElementById('paymentCardInput');

                // Visual feedback
                if (inputType === 'cash') {
                    cashInput.style.borderColor = '#3B82F6';
                    cashInput.style.borderWidth = '3px';
                    cardInput.style.borderColor = '#60A5FA';
                    cardInput.style.borderWidth = '2px';
                } else {
                    cardInput.style.borderColor = '#3B82F6';
                    cardInput.style.borderWidth = '3px';
                    cashInput.style.borderColor = '#FDE047';
                    cashInput.style.borderWidth = '2px';
                }
            }

            // Append Number (Number Pad)
            function appendNumber(value) {
                if (!selectedPaymentMethod) return;

                let input;
                if (selectedPaymentMethod === 'cash') {
                    input = document.getElementById('paymentCashInput');
                } else if (selectedPaymentMethod === 'card') {
                    input = document.getElementById('paymentCardInput');
                } else if (selectedPaymentMethod === 'card_cash') {
                    input = activePaymentInput === 'cash' ?
                        document.getElementById('paymentCashInput') :
                        document.getElementById('paymentCardInput');
                } else if (selectedPaymentMethod === 'credit') {
                    return;
                }

                if (!input) return;

                const currentValue = input.value || '0.00';

                if (value === '.') {
                    if (!currentValue.includes('.')) {
                        input.value = currentValue + value;
                    }
                } else {
                    if (currentValue === '0.00' || currentValue === '0') {
                        input.value = value;
                    } else {
                        input.value = currentValue + value;
                    }
                }

                // Update amounts
                if (selectedPaymentMethod === 'cash' || (selectedPaymentMethod === 'card_cash' && activePaymentInput === 'cash')) {
                    paymentCashAmount = parseFloat(input.value) || 0;
                }
                if (selectedPaymentMethod === 'card' || (selectedPaymentMethod === 'card_cash' && activePaymentInput === 'card')) {
                    paymentCardAmount = parseFloat(input.value) || 0;
                }

                updatePaymentTotals();
            }

            // Backspace Number
            function backspaceNumber() {
                if (!selectedPaymentMethod) return;

                let input;
                if (selectedPaymentMethod === 'cash') {
                    input = document.getElementById('paymentCashInput');
                } else if (selectedPaymentMethod === 'card') {
                    input = document.getElementById('paymentCardInput');
                } else if (selectedPaymentMethod === 'card_cash') {
                    input = activePaymentInput === 'cash' ?
                        document.getElementById('paymentCashInput') :
                        document.getElementById('paymentCardInput');
                } else {
                    return;
                }

                if (!input) return;

                const currentValue = input.value;
                if (currentValue.length > 0) {
                    input.value = currentValue.slice(0, -1);
                    if (input.value === '') {
                        input.value = '0';
                    }
                }

                // Update amounts
                if (selectedPaymentMethod === 'cash' || (selectedPaymentMethod === 'card_cash' && activePaymentInput === 'cash')) {
                    paymentCashAmount = parseFloat(input.value) || 0;
                }
                if (selectedPaymentMethod === 'card' || (selectedPaymentMethod === 'card_cash' && activePaymentInput === 'card')) {
                    paymentCardAmount = parseFloat(input.value) || 0;
                }

                updatePaymentTotals();
            }

            // Clear Number
            function clearNumber() {
                if (!selectedPaymentMethod) return;

                if (selectedPaymentMethod === 'cash') {
                    document.getElementById('paymentCashInput').value = '0.00';
                    paymentCashAmount = 0;
                } else if (selectedPaymentMethod === 'card') {
                    document.getElementById('paymentCardInput').value = '0.00';
                    paymentCardAmount = 0;
                } else if (selectedPaymentMethod === 'card_cash') {
                    if (activePaymentInput === 'cash') {
                        document.getElementById('paymentCashInput').value = '0.00';
                        paymentCashAmount = 0;
                    } else {
                        document.getElementById('paymentCardInput').value = '0.00';
                        paymentCardAmount = 0;
                    }
                }

                updatePaymentTotals();
            }

            // Update Payment Totals
            function updatePaymentTotals() {
                const total = calculateTotal();

                console.log('=== UPDATE PAYMENT TOTALS ===');
                console.log('Total:', total);
                console.log('Selected Method:', selectedPaymentMethod);
                console.log('Cash Amount:', paymentCashAmount);
                console.log('Card Amount:', paymentCardAmount);

                document.getElementById('paymentSubtotal').textContent = total.toFixed(2);
                document.getElementById('paymentTotal').textContent = total.toFixed(2);

                let balance = 0;
                let credit = 0;

                if (selectedPaymentMethod === 'cash') {
                    document.getElementById('paymentCardAmount').textContent = '0.00';

                    if (paymentCashAmount < total) {
                        credit = total - paymentCashAmount;
                    } else {
                        balance = paymentCashAmount - total;
                    }
                } else if (selectedPaymentMethod === 'card') {
                    document.getElementById('paymentCardAmount').textContent = paymentCardAmount.toFixed(2);

                    if (paymentCardAmount < total) {
                        credit = total - paymentCardAmount;
                    } else if (paymentCardAmount > total) {
                        balance = paymentCardAmount - total;
                    }
                } else if (selectedPaymentMethod === 'card_cash') {
                    document.getElementById('paymentCardAmount').textContent = paymentCardAmount.toFixed(2);

                    const totalPaid = paymentCashAmount + paymentCardAmount;
                    console.log('Total Paid (Cash + Card):', totalPaid);

                    if (totalPaid < total) {
                        credit = total - totalPaid;
                    } else {
                        balance = totalPaid - total;
                    }
                } else if (selectedPaymentMethod === 'credit') {
                    document.getElementById('paymentCardAmount').textContent = '0.00';
                    credit = total;
                }

                console.log('Balance:', balance);
                console.log('Credit:', credit);

                document.getElementById('paymentBalance').textContent = balance.toFixed(2);
                document.getElementById('paymentCredit').textContent = credit.toFixed(2);

                // Show/hide credit row
                document.getElementById('creditRow').style.display = credit > 0 ? 'flex' : 'none';

                console.log('=== END UPDATE ===');
            }

            // Calculate Total
            function calculateTotal() {
                return billItems.reduce((sum, item) => {
                    return sum + (parseFloat(item.price) * parseInt(item.quantity));
                }, 0);
            }

            // Validate Payment Input (only allow numbers and one decimal point)
            function validatePaymentInput(event) {
                const charCode = event.which || event.keyCode;
                const charStr = String.fromCharCode(charCode);
                const currentValue = event.target.value;

                // Allow backspace, delete, tab, escape, enter
                if ([8, 9, 27, 13].indexOf(charCode) !== -1) {
                    return true;
                }

                // Allow decimal point only if there isn't one already
                if (charStr === '.' || charStr === ',') {
                    if (currentValue.indexOf('.') !== -1) {
                        event.preventDefault();
                        return false;
                    }
                    return true;
                }

                // Only allow numbers
                if (charStr < '0' || charStr > '9') {
                    event.preventDefault();
                    return false;
                }

                return true;
            }

            // Handle Payment Input Change (when typing with keyboard)
            function handlePaymentInputChange(inputType) {
                let value;

                if (inputType === 'cash') {
                    const input = document.getElementById('paymentCashInput');
                    value = parseFloat(input.value) || 0;
                    paymentCashAmount = value;

                    // Set as active input in CARD & CASH mode
                    if (selectedPaymentMethod === 'card_cash') {
                        activePaymentInput = 'cash';
                        setActivePaymentInput('cash');
                    }
                } else if (inputType === 'card') {
                    const input = document.getElementById('paymentCardInput');
                    value = parseFloat(input.value) || 0;
                    paymentCardAmount = value;

                    // Set as active input in CARD & CASH mode
                    if (selectedPaymentMethod === 'card_cash') {
                        activePaymentInput = 'card';
                        setActivePaymentInput('card');
                    }
                }

                updatePaymentTotals();
            }


            // --- QZ TRAY SECURITY CONFIGURATION (START) ---
            // DISABLED FOR DEVELOPMENT - QZ Tray will run in insecure mode
            // For production, you need proper RSA certificates

            /* CERTIFICATE SETUP - COMMENTED OUT FOR DEVELOPMENT
            
            // 1. Set Certificate Promise
            qz.security.setCertificatePromise(function(resolve, reject) {
                resolve(`-----BEGIN CERTIFICATE-----
                    MIIDozCCAougAwIBAgIUWJpvpJOkleU6lWsqrMKfsq9u6OowDQYJKoZIhvcNAQEL
                    BQAwYTELMAkGA1UEBhMCTEsxEDAOBgNVBAgMB1dlc3Rlcm4xEDAOBgNVBAcMB0Nv
                    bG9tYm8xFTATBgNVBAoMDFJhdm9uIEJha2VyczEXMBUGA1UEAwwOMTI3LjAuMC4x
                    OjgwMDAwHhcNMjUxMTE3MTgwNzI0WhcNMzUxMTE1MTgwNzI0WjBhMQswCQYDVQQG
                    EwJMSzEQMA4GA1UECAwHV2VzdGVybjEQMA4GA1UEBwwHQ29sb21ibzEVMBMGA1UE
                    CgwMUmF2b24gQmFrZXJzMRcwFQYDVQQDDA4xMjcuMC4wLjE6ODAwMDCCASIwDQYJ
                    KoZIhvcNAQEBBQADggEPADCCAQoCggEBANF0JduabBoiZ1M7R28FmCmvUEDYy+2z
                    uz+zQZiBGT3pm3gD2HgZfvhooGywwX2lmEn5Q5wvq3dodcqpd+Nr7xDE6U2QEcGS
                    UEi0aDbTCBY2VIRP5HNP33hDqNOq06akEtJRxGQ43hOLxoSWZjYxe7hIstVfp2fU
                    4j+uycPv9E8Cxo6eIM6NCFfRN1mIbkIIjgVfAmOaJb1y+TbD8z5NxXAfPf31GvXi
                    7AJ3gnr6khs6XyW5umcesBeOijBL+lUyTRU26GQWiduoaeoTToN9UkX3ZEvfPlR7
                    YLYqfRHnT4RJxRs+BcTDMsy0JHI5MGD/Ur/u8uXNgK2mqrfPLado9y0CAwEAAaNT
                    MFEwHQYDVR0OBBYEFMSl/4RhhGD0mRYBD2bH4n+t/cNBMB8GA1UdIwQYMBaAFMSl
                    /4RhhGD0mRYBD2bH4n+t/cNBMA8GA1UdEwEB/wQFMAMBAf8wDQYJKoZIhvcNAQEL
                    BQADggEBADlwDYAu7LGzj+pGROVavOeVczrb8RibbIbXrIViV31iKC1uwXRmtTY1
                    amAX+oEfMry3TIy//BHsJzGkAd6ozfosez33G4bbN8/y1Q9ZvcuaaHPT4DIBYrdR
                    GX/B6TtAm63VxXyjfwrV4OUbbqwdgMtKuviRprB9A+oCE1QPa74p33hgy8UHYOCK
                    g9lFgnRkyrLOb4fh2SmtjHhRV4aZf5CM+UbqBQAMiiuhHLAbqbmhBP3BYzVVZ066
                    9moVkpDvvNADqW3FH6epeBDL8RyQXj2yikCyD3xXJIAih815xLJMh/pOmuqEjHdd
                    NESCtDma6uLcth74mGaBwU3G3KsOCP4=
                -----END CERTIFICATE-----`);
            });

            // 2. Set Signature Promise - Retrieve Signature from the Server
            qz.security.setSignaturePromise(function(toSign) {
                return function(resolve, reject) {
                    // CSRF Token
                    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    var token = tokenMeta ? tokenMeta.content : "";

                    fetch('/qz/sign', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                data: toSign
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.signature) {
                                resolve(data.signature);
                            } else {
                                console.error("Signature Error:", data);
                                reject(data.error || "No signature returned");
                            }
                        })
                        .catch(err => {
                            console.error("Signing Failed:", err);
                            reject(err);
                        });
                };
            });
            */

            console.log('QZ Tray: Running in INSECURE mode (no certificate validation)');

            /**
             * Use QZ Tray to print a PDF to a thermal printer
             * @param {string} pdfBase64 - Base64 encoded PDF string
             * @param {string|null} printerName - Printer name or null for default printer
             * @param {string} jobType - Print job type description (KOT/BOT)
             * @param {boolean} showErrorOnFail - Whether to show error alert on failure
             */
            async function printPDFwithQZ(pdfBase64, printerName = null, jobType = "POS Print", showErrorOnFail = true) {
                try {
                    // 1. Connect to QZ Tray websocket
                    if (!qz.websocket.isActive()) {
                        await qz.websocket.connect();
                        console.log('QZ Tray connected successfully');
                    }

                    // 2. Select printer
                    let printer = printerName;
                    if (!printer) {
                        printer = await qz.printers.getDefault();
                        if (!printer) {
                            throw new Error("Cannot find a default printer.");
                        }
                    }

                    // 3. Prepare the print configuration
                    const config = qz.configs.create(printer);

                    // 4. Printing data preparation
                    const data = [{
                        type: 'pdf',
                        format: 'base64',
                        data: pdfBase64
                    }];

                    // 5. Print the document
                    await qz.print(config, data);

                    console.log(`${jobType} sent to printer: ${printer}`);
                    return true;

                } catch (err) {
                    console.error('QZ Tray Error:', err);
                    if (showErrorOnFail) {
                        let errorMessage = 'Printing failed. Is QZ Tray running?\\n\\n' + err.message;
                        if (err.message && err.message.includes('default printer')) {
                            errorMessage = 'Printing failed. A default printer cannot be found. Please set it in the OS.';
                        } else if (err.message && err.message.includes('Failed to get signature')) {
                            errorMessage = 'Printing failed. Server signature error. Check the backend.';
                        }
                        alert(errorMessage);
                    } else {
                        console.warn("Silent Print Failed (Ignored): " + err.message);
                    }
                    throw err;
                }
            }
            // --- END QZ TRAY CONFIGURATION ---

            /**
             * Generate and Print KOT (Kitchen Order Ticket)
             * @param {Array} foodItems - Array of food items to print
             * @param {Object} orderInfo - Order information (order_number, table_number, etc.)
             */
            async function printKOT(foodItems, orderInfo) {
                if (!foodItems || foodItems.length === 0) {
                    console.log('No food items to print on KOT');
                    return;
                }

                try {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: [80, 297]
                    });

                    let yPosition = 10;
                    const pageWidth = 80;
                    const leftMargin = 5;
                    const rightMargin = 5;

                    // Header
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(18);
                    pdf.text('KITCHEN ORDER TICKET', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 8;

                    pdf.setFontSize(14);
                    pdf.text('(KOT)', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 10;

                    // Restaurant Info
                    pdf.setFontSize(11);
                    pdf.text('Ravon Restaurant', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 10;

                    // Separator
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 6;

                    // Order Information
                    pdf.setFontSize(11);
                    pdf.setFont('courier', 'bold');
                    pdf.text('KOT NO:', leftMargin, yPosition);
                    pdf.text(String(orderInfo.kot_number || orderInfo.order_number || 'N/A'), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    // Order Type
                    pdf.text('TYPE:', leftMargin, yPosition);
                    let typeText = '';
                    if (orderInfo.order_type === 'dine_in' && orderInfo.table_number) {
                        typeText = 'Table ' + String(orderInfo.table_number);
                    } else if (orderInfo.order_type === 'takeaway') {
                        typeText = 'Take Away';
                    } else if (orderInfo.order_type === 'pickme' && orderInfo.pickme_ref) {
                        typeText = 'PickMe - ' + String(orderInfo.pickme_ref);
                    } else if (orderInfo.table_number) {
                        typeText = 'Table ' + String(orderInfo.table_number);
                    } else {
                        typeText = 'Take Away';
                    }
                    pdf.text(typeText, pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('WAITER:', leftMargin, yPosition);
                    pdf.text(orderInfo.user_name || '{{ Auth::user()->name }}', pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('DATE:', leftMargin, yPosition);
                    pdf.text(new Date().toLocaleDateString('en-GB'), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('TIME:', leftMargin, yPosition);
                    pdf.text(new Date().toLocaleTimeString('en-GB', {
                        hour12: false
                    }), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 10;

                    // Items separator
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 8;

                    // Print Items
                    foodItems.forEach((item, index) => {
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(13);

                        let itemName = item.name || item.item_name;
                        if (itemName.length > 22) {
                            itemName = itemName.substring(0, 19) + '...';
                        }

                        pdf.text(itemName, leftMargin, yPosition);
                        yPosition += 6;

                        // Quantity
                        pdf.setFontSize(14);
                        pdf.text(`x ${item.quantity}`, leftMargin + 2, yPosition);
                        yPosition += 8;

                        // Add spacing between items
                        if (index < foodItems.length - 1) {
                            pdf.setLineDashPattern([0.5, 0.5], 0);
                            pdf.setLineWidth(0.2);
                            pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                            pdf.setLineDashPattern([], 0);
                            yPosition += 4;
                        }
                    });

                    // Footer
                    yPosition += 4;
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 8;

                    pdf.setFontSize(10);
                    pdf.text('Thank you!', pageWidth / 2, yPosition, {
                        align: 'center'
                    });

                    // Generate Base64 and Print
                    const pdfBase64 = pdf.output('datauristring').split(',')[1];
                    const kotPrinterName = "Microsoft Print to PDF";
                    await printPDFwithQZ(pdfBase64, kotPrinterName, "KOT", false);
                    console.log('KOT sent to printer successfully');

                } catch (error) {
                    console.error('KOT Generation Error:', error);
                }
            }

            /**
             * Generate and Print BOT (Bar Order Ticket)
             * @param {Array} beverageItems - Array of beverage items to print
             * @param {Object} orderInfo - Order information
             */
            async function printBOT(beverageItems, orderInfo) {
                if (!beverageItems || beverageItems.length === 0) {
                    console.log('No beverage items to print on BOT');
                    return;
                }

                try {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: [80, 297]
                    });

                    let yPosition = 10;
                    const pageWidth = 80;
                    const leftMargin = 5;
                    const rightMargin = 5;

                    // Header
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(18);
                    pdf.text('BAR ORDER TICKET', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 8;

                    pdf.setFontSize(14);
                    pdf.text('(BOT)', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 10;

                    // Restaurant Info
                    pdf.setFontSize(11);
                    pdf.text('Ravon Restaurant', pageWidth / 2, yPosition, {
                        align: 'center'
                    });
                    yPosition += 10;

                    // Separator
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 6;

                    // Order Information
                    pdf.setFontSize(11);
                    pdf.setFont('courier', 'bold');
                    pdf.text('BOT NO:', leftMargin, yPosition);
                    pdf.text(String(orderInfo.bot_number || orderInfo.order_number || 'N/A'), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    // Order Type
                    pdf.text('TYPE:', leftMargin, yPosition);
                    let typeText = '';
                    if (orderInfo.order_type === 'dine_in' && orderInfo.table_number) {
                        typeText = 'Table ' + String(orderInfo.table_number);
                    } else if (orderInfo.order_type === 'takeaway') {
                        typeText = 'Take Away';
                    } else if (orderInfo.order_type === 'pickme' && orderInfo.pickme_ref) {
                        typeText = 'PickMe - ' + String(orderInfo.pickme_ref);
                    } else if (orderInfo.table_number) {
                        typeText = 'Table ' + String(orderInfo.table_number);
                    } else {
                        typeText = 'Take Away';
                    }
                    pdf.text(typeText, pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('WAITER:', leftMargin, yPosition);
                    pdf.text(orderInfo.user_name || '{{ Auth::user()->name }}', pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('DATE:', leftMargin, yPosition);
                    pdf.text(new Date().toLocaleDateString('en-GB'), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 6;

                    pdf.text('TIME:', leftMargin, yPosition);
                    pdf.text(new Date().toLocaleTimeString('en-GB', {
                        hour12: false
                    }), pageWidth - rightMargin, yPosition, {
                        align: 'right'
                    });
                    yPosition += 10;

                    // Items separator
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 8;

                    // Print Items
                    beverageItems.forEach((item, index) => {
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(13);

                        let itemName = item.name || item.item_name;
                        if (itemName.length > 22) {
                            itemName = itemName.substring(0, 19) + '...';
                        }

                        pdf.text(itemName, leftMargin, yPosition);
                        yPosition += 6;

                        // Quantity
                        pdf.setFontSize(14);
                        pdf.text(`x ${item.quantity}`, leftMargin + 2, yPosition);
                        yPosition += 8;

                        // Add spacing between items
                        if (index < beverageItems.length - 1) {
                            pdf.setLineDashPattern([0.5, 0.5], 0);
                            pdf.setLineWidth(0.2);
                            pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                            pdf.setLineDashPattern([], 0);
                            yPosition += 4;
                        }
                    });

                    // Footer
                    yPosition += 4;
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    yPosition += 8;

                    pdf.setFontSize(10);
                    pdf.text('Thank you!', pageWidth / 2, yPosition, {
                        align: 'center'
                    });

                    // Generate Base64 and Print
                    const pdfBase64 = pdf.output('datauristring').split(',')[1];
                    const botPrinterName = "Microsoft Print to PDF";
                    await printPDFwithQZ(pdfBase64, botPrinterName, "BOT", false);
                    console.log('BOT sent to printer successfully');

                } catch (error) {
                    console.error('BOT Generation Error:', error);
                }
            }

            /**
             * Print KOT and BOT based on backend categorization
             * Backend separates items by category (BEVERAGES go to BOT, others to KOT)
             * @param {Object} orderInfo - Order information with kot_number, bot_number, kot_items, bot_items
             */
            async function printKOTandBOT(orderInfo) {
                // Backend provides separate item lists for KOT and BOT

                // Print KOT if there are kitchen items
                if (orderInfo.kot_items && orderInfo.kot_items.length > 0) {
                    console.log('Printing KOT with items:', orderInfo.kot_items);
                    await printKOT(orderInfo.kot_items, orderInfo);
                }

                // Print BOT if there are beverage items
                if (orderInfo.bot_items && orderInfo.bot_items.length > 0) {
                    console.log('Printing BOT with items:', orderInfo.bot_items);
                    await printBOT(orderInfo.bot_items, orderInfo);
                }
            }

            /**
             * Calculate delta items - only items that are NEW or have INCREASED quantities
             * @param {Array} currentItems - Current order items
             * @param {Array} printedItems - Previously printed items
             * @returns {Array} - Only items that need to be printed
             */
            function calculateDeltaItems(currentItems, printedItems) {
                const deltaItems = [];

                currentItems.forEach(currentItem => {
                    // Find if this item was previously printed
                    const printedItem = printedItems.find(p =>
                        p.item_id === currentItem.item_id && p.name === currentItem.name
                    );

                    if (!printedItem) {
                        // This is a completely NEW item - print all quantity
                        deltaItems.push({
                            ...currentItem
                        });
                    } else if (currentItem.quantity > printedItem.quantity) {
                        // Item exists but quantity INCREASED - print only the difference
                        const quantityDifference = currentItem.quantity - printedItem.quantity;
                        deltaItems.push({
                            ...currentItem,
                            quantity: quantityDifference // Only the additional quantity
                        });
                    }
                    // If quantity is same or decreased, don't print anything
                });

                return deltaItems;
            }

            /**
             * Update the printed items tracker
             * @param {Array} items - Items that were just printed
             */
            function updatePrintedItems(items) {
                items.forEach(item => {
                    const existingIndex = printedItems.findIndex(p =>
                        p.item_id === item.item_id && p.name === item.name
                    );

                    if (existingIndex >= 0) {
                        // Update quantity for existing item
                        printedItems[existingIndex].quantity = item.quantity;
                    } else {
                        // Add new item to printed list
                        printedItems.push({
                            item_id: item.item_id,
                            name: item.name,
                            quantity: item.quantity,
                            category: item.category || ''
                        });
                    }
                });
            }

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

                        // --- AUTOMATIC KOT/BOT PRINTING ---
                        // Backend has already separated items into KOT and BOT
                        try {
                            const orderInfo = {
                                order_number: String(result.order_number || 'N/A'),
                                order_type: String(result.order_type || currentOrderType || ''),
                                table_number: String(result.table_number || ''),
                                pickme_ref: String(result.pickme_ref_number || pickMeRefNumber || ''),
                                kot_number: String(result.kot_number || 'N/A'),
                                bot_number: String(result.bot_number || 'N/A'),
                                kot_items: result.kot_items || [],
                                bot_items: result.bot_items || [],
                                user_name: String('{{ Auth::user()->name }}')
                            };

                            console.log('Order Info:', orderInfo);

                            // Print KOT and BOT if items exist
                            if ((orderInfo.kot_items && orderInfo.kot_items.length > 0) ||
                                (orderInfo.bot_items && orderInfo.bot_items.length > 0)) {
                                printKOTandBOT(orderInfo).catch(err => {
                                    console.error('Failed to print KOT/BOT:', err);
                                });
                            } else {
                                console.log('No items to print');
                            }
                        } catch (printError) {
                            console.error('Error initiating KOT/BOT print:', printError);
                        }
                        // --- END AUTOMATIC PRINTING ---

                        // Clear the POS for next order
                        billItems = [];
                        currentOrderId = null;
                        currentOrderType = null;
                        selectedTableId = null;
                        printedItems = []; // Clear printed items tracker for next order

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
            let activePaymentField = 'cash'; // Track which field is active for number pad
            let cashInputValue = '0';
            let cardInputValue = '0';

            // Show Close Order Modal
            window.showCloseOrderModal = function() {
                if (!currentOrderId) {
                    showNotification('No active order to close', 'No Order');
                    return;
                }
                selectedPaymentType = 'cash';
                activePaymentField = 'cash';
                cashInputValue = '0';
                cardInputValue = '0';
                const total = parseFloat(document.getElementById('total').textContent);
                document.getElementById('paymentSubtotal').textContent = total.toFixed(2);
                document.getElementById('paymentTotal').textContent = total.toFixed(2);
                document.getElementById('paymentCashInput').value = '0.00';
                document.getElementById('paymentCardInput').value = '0.00';
                selectPaymentType('cash');
                updatePaymentCalculations();
                document.getElementById('closeOrderModal').classList.remove('hidden');
            };

            // Select Payment Type
            window.selectPaymentType = function(type) {
                selectedPaymentMethod = type; // Fixed: was selectedPaymentType
                selectedPaymentType = type; // Keep for backward compatibility

                console.log('Payment type selected:', type);

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
                
                // Reset input values
                cashInputValue = '0';
                cardInputValue = '0';
                document.getElementById('paymentCashInput').value = '0.00';
                document.getElementById('paymentCardInput').value = '0.00';

                // Get input elements
                const cashInputGroup = document.getElementById('cashInputGroup');
                const cardInputGroup = document.getElementById('cardInputGroup');
                const cardAmountRow = document.getElementById('cardAmountRow');

                // Show/hide inputs based on payment type
                if (type === 'cash') {
                    cashInputGroup.style.display = 'block';
                    cardInputGroup.style.display = 'none';
                    if (cardAmountRow) cardAmountRow.style.display = 'none';
                    activePaymentField = 'cash';
                } else if (type === 'card') {
                    cashInputGroup.style.display = 'none';
                    cardInputGroup.style.display = 'block';
                    if (cardAmountRow) cardAmountRow.style.display = 'flex';
                    activePaymentField = 'card';
                } else if (type === 'card_cash') {
                    // CARD & CASH: Show BOTH inputs
                    cashInputGroup.style.display = 'block';
                    cardInputGroup.style.display = 'block';
                    if (cardAmountRow) cardAmountRow.style.display = 'flex';
                    activePaymentField = 'cash'; // Default to cash field
                } else if (type === 'credit') {
                    cashInputGroup.style.display = 'none';
                    cardInputGroup.style.display = 'none';
                    if (cardAmountRow) cardAmountRow.style.display = 'none';
                    activePaymentField = null;
                }

                updatePaymentCalculations();
            };

            // Set Active Payment Input Field (for clicking on inputs)
            window.setActivePaymentInput = function(fieldType) {
                activePaymentField = fieldType;
                console.log('Active payment field:', fieldType);
                
                // Visual feedback - highlight active field
                const cashInput = document.getElementById('paymentCashInput');
                const cardInput = document.getElementById('paymentCardInput');
                
                if (fieldType === 'cash') {
                    cashInput.style.borderColor = '#3B82F6';
                    cashInput.style.borderWidth = '3px';
                    cardInput.style.borderColor = '#60A5FA';
                    cardInput.style.borderWidth = '2px';
                } else if (fieldType === 'card') {
                    cardInput.style.borderColor = '#3B82F6';
                    cardInput.style.borderWidth = '3px';
                    cashInput.style.borderColor = '#FBBF24';
                    cashInput.style.borderWidth = '2px';
                }
            };

            // Number Pad - Works with active field
            window.appendNumber = function(num) {
                if (!activePaymentField) return; // No active field (e.g., credit mode)
                
                let currentValue = activePaymentField === 'cash' ? cashInputValue : cardInputValue;
                
                if (currentValue === '0' && num !== '.') {
                    currentValue = num;
                } else if (num === '.' && currentValue.includes('.')) {
                    return; // Don't add multiple decimals
                } else {
                    currentValue += num;
                }
                
                // Update the appropriate variable and input field
                if (activePaymentField === 'cash') {
                    cashInputValue = currentValue;
                    document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
                } else if (activePaymentField === 'card') {
                    cardInputValue = currentValue;
                    document.getElementById('paymentCardInput').value = parseFloat(cardInputValue || 0).toFixed(2);
                }
                
                updatePaymentCalculations();
            };

            window.backspaceNumber = function() {
                if (!activePaymentField) return;
                
                let currentValue = activePaymentField === 'cash' ? cashInputValue : cardInputValue;
                currentValue = currentValue.length > 1 ? currentValue.slice(0, -1) : '0';
                
                // Update the appropriate variable and input field
                if (activePaymentField === 'cash') {
                    cashInputValue = currentValue;
                    document.getElementById('paymentCashInput').value = parseFloat(cashInputValue || 0).toFixed(2);
                } else if (activePaymentField === 'card') {
                    cardInputValue = currentValue;
                    document.getElementById('paymentCardInput').value = parseFloat(cardInputValue || 0).toFixed(2);
                }
                
                updatePaymentCalculations();
            };

            window.clearNumber = function() {
                if (!activePaymentField) return;
                
                // Clear the appropriate variable and input field
                if (activePaymentField === 'cash') {
                    cashInputValue = '0';
                    document.getElementById('paymentCashInput').value = '0.00';
                } else if (activePaymentField === 'card') {
                    cardInputValue = '0';
                    document.getElementById('paymentCardInput').value = '0.00';
                }
                
                updatePaymentCalculations();
            };

            // Handle keyboard input changes
            window.handlePaymentInputChange = function(inputType) {
                const input = document.getElementById(inputType === 'cash' ? 'paymentCashInput' : 'paymentCardInput');
                const value = input.value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters
                
                // Update the internal value
                if (inputType === 'cash') {
                    cashInputValue = value || '0';
                } else if (inputType === 'card') {
                    cardInputValue = value || '0';
                }
                
                // Set this as the active field
                activePaymentField = inputType;
                setActivePaymentInput(inputType);
                
                updatePaymentCalculations();
            };

            // Update Calculations
            function updatePaymentCalculations() {
                const total = parseFloat(document.getElementById('paymentTotal').textContent);
                const cashAmount = parseFloat(cashInputValue) || 0;
                const cardAmount = parseFloat(cardInputValue) || 0;
                let balance = 0,
                    credit = 0;

                if (selectedPaymentType === 'cash') {
                    balance = cashAmount - total;
                    if (balance < 0) {
                        credit = Math.abs(balance);
                        balance = 0;
                    }
                } else if (selectedPaymentType === 'card') {
                    // Card payment - show card amount in summary
                    document.getElementById('paymentCardAmount').textContent = cardAmount.toFixed(2);
                    balance = cardAmount - total;
                    if (balance < 0) {
                        credit = Math.abs(balance);
                        balance = 0;
                    }
                } else if (selectedPaymentType === 'card_cash') {
                    // Mixed payment
                    const totalPaid = cashAmount + cardAmount;
                    document.getElementById('paymentCardAmount').textContent = cardAmount.toFixed(2);
                    balance = totalPaid - total;
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
            let isProcessingPayment = false;

            window.completePayment = async function() {
                // Prevent double submission
                if (isProcessingPayment) {
                    console.log('Payment already in progress...');
                    return;
                }

                if (!currentOrderId) {
                    showNotification('No active order', 'Error');
                    return;
                }

                // Check if payment method is selected
                if (!selectedPaymentMethod) {
                    showNotification('Please select a payment method', 'Payment Error');
                    return;
                }

                const total = parseFloat(document.getElementById('paymentTotal').textContent);

                // Get amounts from the actual input fields
                const cashInputElement = document.getElementById('paymentCashInput');
                const cardInputElement = document.getElementById('paymentCardInput');

                const cashAmount = parseFloat(cashInputElement.value) || 0;
                const cardAmount = parseFloat(cardInputElement.value) || 0;

                let amountPaid = 0;
                let paymentMethod = selectedPaymentMethod;

                // Calculate amount paid based on payment method
                if (paymentMethod === 'cash') {
                    amountPaid = cashAmount;
                    if (cashAmount < total) {
                        showNotification('Insufficient cash amount. Total: ' + total.toFixed(2), 'Payment Error');
                        return;
                    }
                } else if (paymentMethod === 'card') {
                    amountPaid = total; // Card payment is exact
                } else if (paymentMethod === 'card_cash') {
                    amountPaid = cashAmount + cardAmount;
                    if (amountPaid < total) {
                        showNotification('Insufficient payment. Total: ' + total.toFixed(2) + ', Paid: ' + amountPaid.toFixed(2), 'Payment Error');
                        return;
                    }
                } else if (paymentMethod === 'credit') {
                    amountPaid = 0; // Credit payment
                }

                isProcessingPayment = true;
                const orderIdToProcess = currentOrderId;

                console.log('Processing payment:', {
                    orderId: orderIdToProcess,
                    paymentMethod: paymentMethod,
                    total: total,
                    amountPaid: amountPaid,
                    cashAmount: cashAmount,
                    cardAmount: cardAmount
                });

                try {
                    const response = await fetch('{{ route("pos.payment") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            order_id: orderIdToProcess,
                            payment_method: paymentMethod,
                            amount_paid: amountPaid,
                            cash_amount: cashAmount,
                            card_amount: cardAmount
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showNotification('Payment completed!', 'Success');

                        // Clear order state immediately
                        billItems = [];
                        currentOrderId = null;
                        currentOrderType = null;
                        selectedTableId = null;

                        // Print receipt automatically using QZ Tray (like KOT/BOT)
                        printReceiptWithQZ(result.order).catch(err => {
                            console.error('Receipt printing error:', err);
                            // Fallback to browser print if QZ Tray fails
                            printReceiptInline(result.order);
                        });

                        renderBill();
                        calculateTotals();
                        closeModal('closeOrderModal');
                        document.getElementById('orderTypeDisplay').textContent = 'Select Order Type';
                        document.getElementById('menuSelectionContainer').classList.replace('flex', 'hidden');
                        document.getElementById('initialStateMessage')?.classList.remove('hidden');
                    } else {
                        showNotification('Error: ' + (result.message || 'Unknown error'), 'Payment Error');
                    }
                } catch (error) {
                    console.error('Payment error:', error);
                    showNotification('Error: ' + error.message, 'System Error');
                } finally {
                    isProcessingPayment = false;
                }
            };

            /**
             * Print Receipt using QZ Tray (Automatic Printing like KOT/BOT)
             * @param {Object} order - Order object with payment details
             */
            async function printReceiptWithQZ(order) {
                if (!order) {
                    console.error('No order data provided for receipt');
                    return;
                }

                try {
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: [80, 297]
                    });

                    let yPosition = 8;
                    const pageWidth = 80;
                    const leftMargin = 5;
                    const rightMargin = 5;
                    const contentWidth = pageWidth - leftMargin - rightMargin;

                    // Header - Restaurant Name
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(16);
                    pdf.text('RAVON RESTAURANT', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 5;

                    pdf.setFontSize(10);
                    pdf.setFont('courier', 'normal');
                    pdf.text('Ravon Restaurant (Pvt) Ltd', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 4;
                    
                    pdf.setFontSize(9);
                    pdf.text('NO 282/A/2, KCTHALAWALA,', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 4;
                    pdf.text('KADUWELA.', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 4;
                    pdf.text('TEL.016-2006007', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 4;
                    pdf.text('Email-ravonrestaurant@gmail.com', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 8;

                    // Invoice Title
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(14);
                    pdf.text('INVOICE', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 8;

                    // Order Information
                    pdf.setFont('courier', 'normal');
                    pdf.setFontSize(9);
                    
                    pdf.text('Invoice #', leftMargin, yPosition);
                    pdf.text(String(order.order_number || order.id), pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    const now = new Date();
                    const dateStr = now.toLocaleDateString('en-GB');
                    const timeStr = now.toLocaleTimeString('en-GB', { hour12: false });
                    
                    pdf.text('Date', leftMargin, yPosition);
                    pdf.text(`:${dateStr} Time ${timeStr}`, pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    pdf.text('Terminal:', leftMargin, yPosition);
                    pdf.text('01', pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    const tableNumber = order.table ? order.table.table_number : 'Take Away';
                    pdf.text('Table # :', leftMargin, yPosition);
                    pdf.text(String(tableNumber), pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    const cashier = order.waiter ? order.waiter.name : 'Cashier User';
                    pdf.text('Cashier :', leftMargin, yPosition);
                    pdf.text(String(cashier), pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 6;

                    // Separator
                    pdf.setLineDashPattern([1, 1], 0);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    pdf.setLineDashPattern([], 0);
                    yPosition += 5;

                    // Items Header
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(9);
                    pdf.text('Item', leftMargin, yPosition);
                    pdf.text('Qty   Amount', pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    pdf.setLineDashPattern([1, 1], 0);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    pdf.setLineDashPattern([], 0);
                    yPosition += 4;

                    // Print Items
                    pdf.setFont('courier', 'normal');
                    pdf.setFontSize(9);

                    const items = order.order_items || order.orderItems || [];
                    items.forEach((item, index) => {
                        const itemName = item.item?.name || item.item_name || 'Unknown Item';
                        const quantity = item.quantity || 0;
                        const subtotal = parseFloat(item.subtotal || 0).toFixed(2);

                        // Item number and name (first line)
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(10);
                        let displayName = `${index + 1}. ${itemName}`;
                        if (displayName.length > 28) {
                            displayName = displayName.substring(0, 25) + '...';
                        }
                        pdf.text(displayName, leftMargin, yPosition);
                        yPosition += 5;

                        // Second line: Quantity and Amount (right-aligned)
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);
                        
                        // Quantity on the left side of second line
                        pdf.text(`${quantity}x`, leftMargin + 10, yPosition);
                        
                        // Amount on the right side
                        pdf.text(subtotal, pageWidth - rightMargin, yPosition, { align: 'right' });
                        yPosition += 6;
                    });

                    // Separator
                    yPosition += 2;
                    pdf.setLineDashPattern([1, 1], 0);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    pdf.setLineDashPattern([], 0);
                    yPosition += 4;

                    // Subtotal
                    pdf.setFont('courier', 'normal');
                    pdf.setFontSize(10);
                    pdf.text('Sub Total', leftMargin, yPosition);
                    pdf.text(parseFloat(order.subtotal || 0).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 5;

                    // Total Separator (thick line)
                    pdf.setLineWidth(0.5);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    pdf.setLineWidth(0.2);
                    yPosition += 5;

                    // Grand Total
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(11);
                    pdf.text('Total', leftMargin, yPosition);
                    pdf.text(parseFloat(order.total_amount || 0).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 7;

                    // Payment Information
                    pdf.setFont('courier', 'normal');
                    pdf.setFontSize(9);

                    const paymentMethod = order.payment?.payment_method?.toUpperCase() || 'CASH';
                    pdf.text('Payment Method', leftMargin, yPosition);
                    pdf.text(paymentMethod, pageWidth - rightMargin, yPosition, { align: 'right' });
                    yPosition += 4;

                    const cashAmount = order.payment?.cash_amount || 0;
                    const cardAmount = order.payment?.card_amount || 0;
                    const creditAmount = order.payment?.credit_amount || 0;
                    const changeAmount = order.payment?.change_amount || 0;

                    if (cashAmount > 0) {
                        pdf.text('Cash', leftMargin, yPosition);
                        pdf.text(parseFloat(cashAmount).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                        yPosition += 4;
                    }

                    if (cardAmount > 0) {
                        pdf.text('Card', leftMargin, yPosition);
                        pdf.text(parseFloat(cardAmount).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                        yPosition += 4;
                    }

                    if (creditAmount > 0) {
                        pdf.text('Credit', leftMargin, yPosition);
                        pdf.text(parseFloat(creditAmount).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                        yPosition += 4;
                    }

                    if (changeAmount > 0) {
                        pdf.text('Change', leftMargin, yPosition);
                        pdf.text(parseFloat(changeAmount).toFixed(2), pageWidth - rightMargin, yPosition, { align: 'right' });
                        yPosition += 4;
                    }

                    yPosition += 3;

                    // Footer
                    pdf.setFont('courier', 'bold');
                    pdf.setFontSize(10);
                    pdf.text('THANK YOU, COME AGAIN.', pageWidth / 2, yPosition, { align: 'center' });
                    yPosition += 6;

                    pdf.setLineDashPattern([1, 1], 0);
                    pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                    pdf.setLineDashPattern([], 0);
                    yPosition += 4;

                    pdf.setFont('courier', 'normal');
                    pdf.setFontSize(8);
                    pdf.text('Software By SKM Labs', pageWidth / 2, yPosition, { align: 'center' });

                    // Generate Base64 and Print
                    const pdfBase64 = pdf.output('datauristring').split(',')[1];
                    const receiptPrinterName = "Microsoft Print to PDF"; // Configure printer name
                    await printPDFwithQZ(pdfBase64, receiptPrinterName, "Receipt", false);
                    console.log('Receipt sent to printer successfully');

                } catch (error) {
                    console.error('Receipt Generation Error:', error);
                    throw error;
                }
            }

            // Print Receipt Inline (Fallback method using browser print dialog)
            function printReceiptInline(order) {
                const receiptHTML = generateReceiptHTML(order);

                // Create a hidden iframe for printing
                let printFrame = document.getElementById('print-frame');
                if (!printFrame) {
                    printFrame = document.createElement('iframe');
                    printFrame.id = 'print-frame';
                    printFrame.style.display = 'none';
                    document.body.appendChild(printFrame);
                }

                const doc = printFrame.contentWindow.document;
                doc.open();
                doc.write(receiptHTML);
                doc.close();

                // Wait for content to load then print
                setTimeout(() => {
                    printFrame.contentWindow.print();
                }, 500);
            }

            // Generate Receipt HTML
            function generateReceiptHTML(order) {
                console.log('Generating receipt for order:', order);

                const now = new Date();
                const dateStr = now.toLocaleDateString('en-GB');
                const timeStr = now.toLocaleTimeString('en-US', {
                    hour12: false
                });

                let itemsHTML = '';
                let itemCount = 0;

                // Handle both order_items and orderItems (Laravel uses snake_case or camelCase)
                const items = order.order_items || order.orderItems || [];

                if (items.length === 0) {
                    console.warn('No items found in order');
                    itemsHTML = '<div class="item-row">No items</div>';
                } else {
                    items.forEach(item => {
                        itemCount++;
                        const itemName = item.item?.name || item.item_name || 'Unknown Item';
                        const itemCode = item.item?.item_code || item.item_code || '';
                        const unitPrice = parseFloat(item.unit_price || 0).toFixed(2);
                        const quantity = item.quantity || 0;
                        const subtotal = parseFloat(item.subtotal || 0).toFixed(2);

                        itemsHTML += `
                            <div class="item-row">
                                <div class="item-line">
                                    <span>${itemCount}</span>
                                    <span>${itemName}</span>
                                </div>
                                <div class="item-line">
                                    <span>${itemCode}</span>
                                    <span>${unitPrice} x ${quantity}</span>
                                    <span>${subtotal}</span>
                                </div>
                            </div>
                        `;
                    });
                }

                const paymentMethod = order.payment?.payment_method?.toUpperCase() || 'CASH';
                const cashAmount = order.payment?.cash_amount || 0;
                const cardAmount = order.payment?.card_amount || 0;
                const creditAmount = order.payment?.credit_amount || 0;
                const changeAmount = order.payment?.change_amount || 0;

                console.log('Payment details:', {
                    paymentMethod,
                    cashAmount,
                    cardAmount,
                    creditAmount,
                    changeAmount
                });

                return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - ${order.order_number || 'Order #' + order.id}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            width: 80mm;
            padding: 5mm;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 1px;
        }
        
        .header .subtitle {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .header .address {
            font-size: 9px;
            line-height: 1.4;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        
        .divider-thick {
            border-top: 2px solid #000;
            margin: 5px 0;
        }
        
        .invoice-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 8px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 10px;
        }
        
        .info-row .label {
            min-width: 80px;
        }
        
        .section {
            margin: 8px 0;
        }
        
        .items-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 3px;
            padding-bottom: 3px;
            border-bottom: 1px dashed #000;
        }
        
        .item-row {
            margin-bottom: 5px;
        }
        
        .item-line {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 5px;
        }
        
        .item-line:first-child {
            font-weight: bold;
        }
        
        .totals {
            margin-top: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .total-row.grand {
            font-weight: bold;
            font-size: 13px;
            padding-top: 3px;
            margin-top: 3px;
        }
        
        .payment-info {
            margin-top: 8px;
        }
        
        .credit-note {
            margin-top: 5px;
            font-size: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }
        
        .footer-note {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 9px;
        }
        
        @media print {
            body {
                width: 80mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RAVON RESTAURANT</h1>
        <div class="subtitle">Ravon Restaurant (Pvt) Ltd</div>
        <div class="address">
            NO 282/A/2, KCTHALAWALA,<br>
            KADUWELA.<br>
            TEL.016-2006007<br>
            Email-ravonrestaurant@gmail.com
        </div>
    </div>
    
    <div class="invoice-title">INVOICE</div>
    
    <div class="section">
        <div class="info-row">
            <span class="label">Invoice #</span>
            <span>${order.order_number || order.id}</span>
        </div>
        <div class="info-row">
            <span class="label">Date</span>
            <span>:${dateStr} Time ${timeStr}</span>
        </div>
        <div class="info-row">
            <span class="label">Terminal:</span>
            <span>01</span>
        </div>
        <div class="info-row">
            <span class="label">Table # :</span>
            <span>${order.table ? order.table.table_number : 'Take Away'}</span>
        </div>
        <div class="info-row">
            <span class="label">Cashier :</span>
            <span>${order.waiter ? order.waiter.name : 'Cashier'}</span>
        </div>
    </div>
    
    <div class="divider"></div>
    
    <div class="items-header">
        <span>In Item Price</span>
        <span>Qty Amount</span>
    </div>
    
    <div class="divider"></div>
    
    ${itemsHTML}
    
    <div class="divider"></div>
    
    <div class="totals">
        <div class="total-row">
            <span>Sub Total</span>
            <span>${parseFloat(order.subtotal).toFixed(2)}</span>
        </div>
    </div>
    
    <div class="divider-thick"></div>
    
    <div class="total-row grand">
        <span>Total</span>
        <span>${parseFloat(order.total_amount).toFixed(2)}</span>
    </div>
    
    <div class="payment-info">
        <div class="total-row">
            <span>Payment Method</span>
            <span>${paymentMethod}</span>
        </div>
        ${cashAmount > 0 ? `
        <div class="total-row">
            <span>Cash</span>
            <span>${parseFloat(cashAmount).toFixed(2)}</span>
        </div>
        ` : ''}
        ${cardAmount > 0 ? `
        <div class="total-row">
            <span>Card</span>
            <span>${parseFloat(cardAmount).toFixed(2)}</span>
        </div>
        ` : ''}
        ${creditAmount > 0 ? `
        <div class="total-row">
            <span>Credit</span>
            <span>${parseFloat(creditAmount).toFixed(2)}</span>
        </div>
        ` : ''}
        ${parseFloat(changeAmount) > 0 ? `
        <div class="total-row">
            <span>Change</span>
            <span>${parseFloat(changeAmount).toFixed(2)}</span>
        </div>
        ` : ''}
    </div>
    
    <div class="footer">
        <div style="font-weight: bold; margin-bottom: 5px;">THANK YOU, COME AGAIN.</div>
        <div class="footer-note">
            Software By SKM Labs
        </div>
    </div>
</body>
</html>
                `;
            }

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

            // Print Receipt (Inline Thermal Receipt)
            window.printReceipt = async function(orderId) {
                try {
                    // Fetch order details using POS route
                    const response = await fetch(`/pos/order/${orderId}`);
                    const result = await response.json();

                    if (result.success && result.order) {
                        // Map 'items' to 'orderItems' for the receipt generator
                        const orderData = {
                            ...result.order,
                            orderItems: result.order.items || result.order.orderItems || result.order.order_items,
                            order_items: result.order.items || result.order.orderItems || result.order.order_items
                        };
                        printReceiptInline(orderData);
                    } else {
                        showNotification('Failed to load order details', 'Error');
                    }
                } catch (error) {
                    console.error('Error printing receipt:', error);
                    showNotification('Error printing receipt: ' + error.message, 'Error');
                }
            };
        </script>
        @endsection