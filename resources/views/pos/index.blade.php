@extends('layouts.app', ['hideNavigation' => true])

@section('title', 'POS - Ravon Restaurant')

@section('content')
    <div class="h-screen flex flex-col bg-gray-900">
        <!-- Top Bar -->
        <div class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-white">Point of Sale</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-400" id="pos-clock"></span>
                <span class="text-sm text-white font-semibold">{{ Auth::user()->name }}</span>
                <button onclick="lockScreen()"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
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
                        <svg class="w-16 h-16 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p>No items added</p>
                    </div>
                </div>

                <!-- Bill Totals -->
                <div class="p-4 border-t border-gray-700 bg-gray-900">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-400">
                            <span>Sub Total (Base)</span>
                            <span id="subTotalBase">0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-400">
                            <span>SSCL ({{ $ssclRate ?? 0 }}%)</span>
                            <span id="ssclAmount">0.00</span>
                        </div>
                        <div
                            class="flex justify-between text-gray-400 pb-2 border-b border-gray-700 hover:border-gray-500 transition">
                            <span>VAT ({{ $vatRate ?? 0 }}%)</span>
                            <span id="vatAmount">0.00</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-emerald-400 pt-1">
                            <span>GRAND TOTAL</span>
                            <span class="flex gap-1 items-center">
                                <span class="text-xs text-emerald-600">LKR</span>
                                <span id="total">0.00</span>
                            </span>
                        </div>
                    </div>

                    <!-- VOID and Print Invoice Buttons -->
                    <div class="mt-4 space-y-2">
                        <button id="voidButton" onclick="openVoidPinModal()" disabled
                            class="w-full bg-gray-700 text-gray-500 font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md flex items-center justify-center gap-2 cursor-not-allowed disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Void
                        </button>

                        <button onclick="printCurrentInvoice()"
                            class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Invoice
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side - Menu and Actions -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <!-- Menu Tabs -->
                <div id="menuSelectionContainer" class="hidden flex-col h-full min-w-0">
                    <div class="bg-gray-800 border-b border-gray-700 w-full overflow-hidden">
                        <div class="w-full overflow-x-auto" id="categoryTabs"
                            style="scrollbar-width: thin; scrollbar-color: #4B5563 #1F2937;">
                            <style>
                                #categoryTabs::-webkit-scrollbar {
                                    height: 6px;
                                }

                                #categoryTabs::-webkit-scrollbar-track {
                                    background: #1F2937;
                                }

                                #categoryTabs::-webkit-scrollbar-thumb {
                                    background: #4B5563;
                                    border-radius: 3px;
                                }

                                #categoryTabs::-webkit-scrollbar-thumb:hover {
                                    background: #6B7280;
                                }
                            </style>
                            <div class="inline-flex whitespace-nowrap px-2 py-1">
                                @foreach($categories as $category)
                                    <button
                                        class="category-tab px-6 py-3 {{ $loop->first ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }} transition font-semibold rounded-t-lg flex-shrink-0 mr-1"
                                        onclick="filterByCategory({{ $category->id }})" data-id="{{ $category->id }}">
                                        {{ strtoupper($category->name) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>


                    <div class="flex-1 flex flex-col overflow-hidden">
                        <!-- Menu Items Grid (Scrollable) -->
                        <div class="flex-1 overflow-y-auto p-4">
                            <!-- Category Filters Removed -->

                            <!-- Search Bar -->
                            <div class="mb-4">
                                <div class="flex gap-2">
                                    <input type="text" id="searchItems" placeholder="Search items..."
                                        class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                                    <button
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Items Grid -->
                            <div class="grid grid-cols-4 gap-3" id="itemsGrid">
                                @foreach($categories as $category)
                                    @foreach($category->availableItems as $item)
                                        @php
                                            $pickmePrice = $item->itemPrices()->where('price_type', 'pickme')->whereNull('item_modifier_id')->first();

                                            // Prepare modifiers with their Pick Me prices
                                            $modifiersWithPrices = $item->modifiers->map(function ($modifier) {
                                                $modPickmePrice = $modifier->itemPrices()->where('price_type', 'pickme')->first();
                                                return [
                                                    'id' => $modifier->id,
                                                    'name' => $modifier->name,
                                                    'type' => $modifier->type,
                                                    'price_adjustment' => $modifier->price_adjustment,
                                                    'pickme_price' => $modPickmePrice ? $modPickmePrice->price : null,
                                                    'is_active' => $modifier->is_active,
                                                    'pork_available' => $modifier->pork_available,
                                                ];
                                            });
                                        @endphp
                                        <button
                                            class="p-5 bg-gray-700 text-white rounded-lg hover:bg-blue-600 transition border border-gray-600 hover:border-blue-500 text-center flex items-center justify-center h-full"
                                            onclick="selectItem({{ $item->id }}, '{{ $item->name }}', {{ $item->price }}, {{ json_encode($modifiersWithPrices) }}, {{ $pickmePrice ? $pickmePrice->price : 'null' }}, {{ $item->pork_available ? 'true' : 'false' }})"
                                            data-category="{{ $category->id }}">
                                            <div class="font-semibold text-lg">{{ $item->name }}</div>
                                        </button>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>

                        <!-- Portion Selection Area (Fixed Box at Bottom - Always Visible, Content Changes) -->
                        <div id="portionSelectionArea" class="bg-gray-700 border-t-2 border-blue-500 relative"
                            style="height: 294px; min-height: 294px; max-height: 294px;">
                            <button onclick="cancelPortionSelection()" id="closePortionBtn"
                                class="hidden absolute top-3 right-3 text-gray-400 hover:text-white z-10">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div class="p-4 h-full flex flex-col">
                                <h3 class="text-lg font-bold text-white mb-4">Select Portion / Size</h3>
                                <div id="portionOptions"
                                    class="grid grid-cols-3 gap-3 flex-1 content-start overflow-y-auto">
                                    <!-- Content will be dynamically updated by JavaScript -->
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Initial State Message -->
                <div id="initialStateMessage" class="flex-1 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div
                        class="bg-emerald-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-emerald-600 transition">
                        Table Order
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openTakeAwayModal()">
                    <div class="bg-white text-amber-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div
                        class="bg-amber-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-amber-500 transition">
                        Take Away
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openPickMeRefModal()">
                    <div class="bg-white text-pink-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <span class="font-bold text-sm">Pick</span>
                    </div>
                    <div
                        class="bg-pink-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-pink-500 transition">
                        PickMe Food
                    </div>
                </button>

                <button id="placeOrderBtn" class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="checkout()">
                    <div class="bg-white text-green-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                    </div>
                    <div id="placeOrderBtnText"
                        class="bg-green-500 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-green-600 transition">
                        Place Order
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openOrderCheckModal()">
                    <div class="bg-white text-blue-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div
                        class="bg-blue-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-blue-500 transition">
                        Open Checks
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="showCloseOrderModal()">
                    <div class="bg-white text-yellow-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div
                        class="bg-yellow-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-yellow-500 transition">
                        Close Order
                    </div>
                </button>



                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="cancelOrder()">
                    <div class="bg-white text-red-600 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div
                        class="bg-red-600 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-red-700 transition">
                        Cancel
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="splitOrder()">
                    <div class="bg-white text-purple-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div
                        class="bg-purple-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-purple-500 transition">
                        Split Order
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="mergeOrder()">
                    <div class="bg-white text-teal-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div
                        class="bg-teal-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-teal-500 transition">
                        Merge Order
                    </div>
                </button>

                <button id="transferTableBtn" class="w-full h-14 flex items-stretch shadow-sm group mb-2"
                    onclick="openTableTransferModal()">
                    <div class="bg-white text-cyan-500 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div
                        class="bg-cyan-400 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-cyan-500 transition">
                        Table Transfer
                    </div>
                </button>

                <button class="w-full h-14 flex items-stretch shadow-sm group mb-2" onclick="openClosedOrdersModal()">
                    <div class="bg-white text-rose-400 p-3 rounded-l-lg flex items-center justify-center w-14">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div
                        class="bg-rose-300 text-white flex-1 flex items-center justify-center font-bold text-base rounded-r-lg group-hover:bg-rose-400 transition">
                        Print Copy
                    </div>
                </button>
            </div>



            <!-- Table Selection Modal -->
            <div id="tableModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">Select Table</h2>
                        <button onclick="closeModal('tableModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <div class="grid grid-cols-5 gap-4" id="tableGrid">
                            <!-- Tables will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Transfer Modal -->
            <div id="tableTransferModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Transfer Table</h2>
                            <p class="text-sm text-gray-400 mt-1">Select a new table for this order</p>
                        </div>
                        <button onclick="closeModal('tableTransferModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <div class="grid grid-cols-5 gap-4" id="tableTransferGrid">
                            <!-- Tables will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Merge Order Modal -->
            <div id="mergeOrderModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Merge Order</h2>
                            <p class="text-sm text-gray-400 mt-1">Select an order to merge into the current order</p>
                        </div>
                        <button onclick="closeModal('mergeOrderModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <div id="mergeOrderGrid" class="grid grid-cols-3 gap-4">
                            <!-- Orders will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- PickMe Reference Number Modal -->
            <div id="pickMeRefModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">PickMe Food Order</h2>
                        <button onclick="closeModal('pickMeRefModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                                Reference Number *
                            </label>
                            <input type="text" id="pickMeRefNumber" placeholder="Enter PickMe reference number"
                                class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-pink-500 text-lg font-semibold"
                                autocomplete="off" onkeypress="if(event.key === 'Enter') confirmPickMeRef()">
                        </div>

                        <button onclick="confirmPickMeRef()"
                            class="w-full px-4 py-3 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition font-bold text-lg flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Continue to Order</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Open Checks Modal -->
            <div id="openChecksModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">Open Checks</h2>
                        <button onclick="closeModal('openChecksModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="openChecksContainer" class="space-y-3 max-h-96 overflow-y-auto">
                        <!-- Open orders will be loaded dynamically -->
                    </div>
                </div>
            </div>

            <!-- Close Order Payment Modal -->
            <div id="closeOrderModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full mx-4"
                    style="max-height: 90vh; overflow-y: auto;">
                    <!-- Header -->
                    <div
                        class="flex justify-between items-center p-6 border-b border-gray-700 bg-gradient-to-r from-blue-600 to-blue-700">
                        <div class="flex items-center space-x-3">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h2 class="text-2xl font-bold text-white">Payment Processing</h2>
                        </div>
                        <button onclick="closeModal('closeOrderModal')" class="text-white hover:text-gray-200 transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
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
                                    <button onclick="selectPaymentType('cash')" id="paymentTypeCash"
                                        class="payment-type-btn bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                        CASH
                                    </button>
                                    <button onclick="selectPaymentType('card')" id="paymentTypeCard"
                                        class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                        CARD
                                    </button>
                                    <button onclick="selectPaymentType('card_cash')" id="paymentTypeCardCash"
                                        class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                        CARD & CASH
                                    </button>
                                    <button onclick="selectPaymentType('credit')" id="paymentTypeCredit"
                                        class="payment-type-btn bg-gray-700 hover:bg-gray-600 text-white font-bold py-4 rounded-lg transition border-2 border-transparent">
                                        CREDIT
                                    </button>
                                </div>
                            </div>

                            <!-- Number Pad -->
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <div class="grid grid-cols-3 gap-2">
                                    <button onclick="appendNumber('7')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">7</button>
                                    <button onclick="appendNumber('8')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">8</button>
                                    <button onclick="appendNumber('9')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">9</button>

                                    <button onclick="appendNumber('4')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">4</button>
                                    <button onclick="appendNumber('5')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">5</button>
                                    <button onclick="appendNumber('6')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">6</button>

                                    <button onclick="appendNumber('1')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">1</button>
                                    <button onclick="appendNumber('2')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">2</button>
                                    <button onclick="appendNumber('3')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">3</button>

                                    <button onclick="appendNumber('0')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">0</button>
                                    <button onclick="appendNumber('.')"
                                        class="numberpad-btn bg-gray-600 hover:bg-gray-500 text-white font-bold text-xl py-4 rounded-lg transition">.</button>
                                    <button onclick="backspaceNumber()"
                                        class="numberpad-btn bg-yellow-500 hover:bg-yellow-600 text-white font-bold text-xl py-4 rounded-lg transition">
                                        <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                                        </svg>
                                    </button>

                                    <button onclick="clearNumber()"
                                        class="numberpad-btn bg-red-600 hover:bg-red-700 text-white font-bold text-xl py-4 rounded-lg transition col-span-3">C</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Payment Summary -->
                        <div class="space-y-4">
                            <div class="bg-gray-700 rounded-lg p-4 space-y-3">
                                <!-- Base Total -->
                                <div class="flex justify-between items-center text-gray-300">
                                    <span class="font-semibold">Sub Total (Base)</span>
                                    <span class="font-bold" id="paymentSubtotalBase">0.00</span>
                                </div>

                                <!-- SSCL -->
                                <div class="flex justify-between items-center text-gray-300">
                                    <span class="font-semibold">SSCL ({{ $ssclRate ?? 0 }}%)</span>
                                    <span class="font-bold" id="paymentSscl">0.00</span>
                                </div>

                                <!-- VAT -->
                                <div class="flex justify-between items-center text-gray-300">
                                    <span class="font-semibold">VAT ({{ $vatRate ?? 0 }}%)</span>
                                    <span class="font-bold" id="paymentVat">0.00</span>
                                </div>

                                <!-- Total -->
                                <div class="flex justify-between items-center py-2 border-t border-gray-600">
                                    <span class="font-bold text-blue-400 text-lg">GRAND TOTAL ——→</span>
                                    <span class="font-bold text-blue-400 text-2xl" id="paymentTotal">0.00</span>
                                </div>

                                <!-- Card Amount -->
                                <div class="flex justify-between items-center text-gray-300" id="cardAmountRow"
                                    style="display: none;">
                                    <span>Card</span>
                                    <span id="paymentCardAmount">0.00</span>
                                </div>

                                <!-- Cash Amount Input -->
                                <div id="cashInputGroup">
                                    <label class="block text-sm text-gray-400 mb-2">Cash Amount</label>
                                    <input type="text" id="paymentCashInput" onclick="setActivePaymentInput('cash')"
                                        oninput="handlePaymentInputChange('cash')"
                                        onkeypress="return validatePaymentInput(event)"
                                        class="w-full px-4 py-3 bg-yellow-50 text-gray-900 rounded-lg font-bold text-xl text-right border-2 border-yellow-400 focus:outline-none focus:border-yellow-500"
                                        value="0.00">
                                </div>

                                <!-- Card Amount Input -->
                                <div id="cardInputGroup" class="mt-3" style="display: none;">
                                    <label class="block text-sm text-gray-400 mb-2">Card Amount</label>
                                    <input type="text" id="paymentCardInput" onclick="setActivePaymentInput('card')"
                                        oninput="handlePaymentInputChange('card')"
                                        onkeypress="return validatePaymentInput(event)"
                                        class="w-full px-4 py-3 bg-blue-50 text-gray-900 rounded-lg font-bold text-xl text-right border-2 border-blue-400 focus:outline-none focus:border-blue-500"
                                        value="0.00">
                                </div>

                                <!-- Balance -->
                                <div class="flex justify-between items-center py-2 border-t border-gray-600"
                                    id="balanceRow">
                                    <span class="font-bold text-green-400">Balance ——→</span>
                                    <span class="font-bold text-green-400 text-xl" id="paymentBalance">0.00</span>
                                </div>

                                <!-- Credit -->
                                <div class="flex justify-between items-center bg-red-50 rounded p-2" id="creditRow"
                                    style="display: none;">
                                    <span class="font-bold text-red-600">Credit ——→</span>
                                    <span class="font-bold text-red-600 text-xl" id="paymentCredit">0.00</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <button onclick="closeModal('closeOrderModal')"
                                    class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg font-bold transition flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    <span>Back</span>
                                </button>
                                <button onclick="completePayment()"
                                    class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold transition flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Pay</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Closed Orders Modal -->
            <div id="closedOrdersModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4" style="max-height: 90vh; overflow-y: auto;">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">Closed Orders / Bills</h2>
                        <button onclick="closeModal('closedOrdersModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="closedOrdersContainer" class="space-y-3 max-h-96 overflow-y-auto">
                        <!-- Closed orders will be loaded dynamically -->
                    </div>
                </div>
            </div>

            <!-- Checkout Modal (OLD - Keep for compatibility) -->
            <div id="checkoutModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">Payment</h2>
                        <button onclick="closeModal('checkoutModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
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
                            <select id="paymentMethod"
                                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="split">Split Payment</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-400 mb-2">Amount Paid</label>
                            <input type="number" id="amountPaid"
                                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500"
                                step="0.01">
                        </div>

                        <div class="flex justify-between text-white">
                            <span>Change:</span>
                            <span class="font-bold" id="changeAmount">0.00</span>
                        </div>

                        <button onclick="processPayment()"
                            class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                            Complete Payment
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notification Modal -->
            <div id="notificationModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div
                    class="bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform transition-all scale-100">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-900/50 mb-4">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-bold text-white mb-2" id="notificationTitle">Notification</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-300" id="notificationMessage">Message goes here</p>
                        </div>
                        <div class="mt-5">
                            <button onclick="closeNotification()"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div id="confirmationModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div
                    class="bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform transition-all scale-100">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-900/50 mb-4">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-bold text-white mb-2" id="confirmationTitle">Confirm Action</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-300" id="confirmationMessage">Are you sure?</p>
                        </div>
                        <div class="mt-5 flex justify-center space-x-3">
                            <button onclick="closeConfirmation()"
                                class="inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm transition">
                                Cancel
                            </button>
                            <button id="confirmBtn"
                                class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm transition">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Supervisor PIN Modal (for VOID authorization) -->
            <div id="supervisorPinModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-white">Supervisor Authorization</h2>
                        <button onclick="closeModal('supervisorPinModal')" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Enter Supervisor PIN *
                            </label>
                            <input type="password" id="supervisorPinInput" placeholder="Enter 4-digit PIN" maxlength="4"
                                class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-orange-500 text-lg font-semibold text-center tracking-widest"
                                autocomplete="off" onkeypress="if(event.key === 'Enter') verifySupervisorPin()">
                            <p id="supervisorPinError" class="text-red-400 text-sm mt-2 hidden">Invalid PIN. Please try
                                again.</p>
                        </div>

                        <button onclick="verifySupervisorPin()"
                            class="w-full px-4 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition font-bold text-lg flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Verify PIN</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VOID Items Modal -->
            <div id="voidItemsModal"
                class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl p-6 max-w-2xl w-full mx-4" style="max-height: 90vh; overflow-y: auto;">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-orange-500 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-white">Void Items</h2>
                        </div>
                        <button onclick="closeVoidItemsModal()" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-gray-400 text-sm mb-4">Select items to void (reduce quantity). Only previously ordered
                        items can be voided.</p>

                    <!-- Add Void Item Row -->
                    <div class="bg-gray-700 rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-6">
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Select Item</label>
                                <select id="voidItemDropdown"
                                    class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg border border-gray-500 focus:outline-none focus:border-orange-500">
                                    <option value="">-- Select an item --</option>
                                </select>
                            </div>
                            <div class="col-span-3">
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Void Qty</label>
                                <input type="number" id="voidQuantityInput" min="1" value="1"
                                    class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg border border-gray-500 focus:outline-none focus:border-orange-500 text-center">
                            </div>
                            <div class="col-span-3">
                                <button onclick="addVoidItem()"
                                    class="w-full px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold transition">
                                    + Add
                                </button>
                            </div>
                        </div>
                        <p id="voidItemMaxQty" class="text-gray-400 text-xs mt-2"></p>
                    </div>

                    <!-- Void Items Table -->
                    <div class="bg-gray-700 rounded-lg overflow-hidden mb-4">
                        <table class="w-full">
                            <thead class="bg-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-200">Item Name</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-200">Current Qty</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-200">Void Qty</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-200">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="voidItemsTableBody">
                                <tr id="noVoidItemsRow">
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                        No items added to void. Select items above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button onclick="closeVoidItemsModal()"
                            class="flex-1 px-4 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg font-bold transition">
                            Cancel
                        </button>
                        <button onclick="processVoidItems()" id="confirmVoidBtn"
                            class="flex-1 px-4 py-3 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-bold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Confirm Void
                        </button>
                    </div>
                </div>
            </div>

            {{-- Pork Ingredient Selector Modal --}}
            <div id="porkIngredientModal"
                class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex items-center justify-center">
                <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 border border-pink-500/50">
                    <div class="p-4 border-b border-gray-700">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🐷</span>
                            <h3 class="text-lg font-bold text-white">Select Ingredients</h3>
                        </div>
                        <p class="text-sm text-gray-400 mt-1" id="porkModalItemName"></p>
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-gray-400 mb-3">Uncheck any ingredients to exclude from this item:</p>
                        <div id="porkIngredientsList" class="space-y-2 max-h-64 overflow-y-auto">
                            <!-- Ingredients will be loaded here -->
                        </div>
                        <div id="porkLoadingSpinner" class="hidden text-center py-4">
                            <svg class="animate-spin h-8 w-8 text-pink-500 mx-auto" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p class="text-gray-400 text-sm mt-2">Loading ingredients...</p>
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-700 flex gap-3">
                        <button onclick="closePorkModal()"
                            class="flex-1 px-4 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg font-bold transition">
                            Cancel
                        </button>
                        <button onclick="confirmPorkSelection()" id="confirmPorkBtn"
                            class="flex-1 px-4 py-3 bg-pink-600 hover:bg-pink-500 text-white rounded-lg font-bold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Confirm & Add
                        </button>
                    </div>
                </div>
            </div>

            {{-- QZ Tray for Thermal Printing --}}
            <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.js"></script>
            {{-- jsPDF for PDF generation --}}
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

            <script>

                const itemTaxInfo = {
                    @foreach($categories as $category)
                        @foreach($category->availableItems as $item)
                                                "{{ $item->id }}": {
                            vat_available: {{ $item->vat_available ? 'true' : 'false' }},
                            sscl_available: {{ $item->sscl_available ? 'true' : 'false' }}
                                                },
                        @endforeach
                    @endforeach
                            };

                // Global variables
                let billItems = [];
                let currentOrderType = null;
                let selectedTableId = null;
                let currentOrderId = null; // Track current order for updates
                let pickMeRefNumber = null; // Store PickMe reference number

                // Track items that have already been sent to kitchen/bar
                let printedItems = []; // Stores items that have been printed with their quantities

                // VOID functionality variables
                let originalOrderItems = []; // Items loaded from existing order (can be voided)
                let voidItemsList = []; // Items selected to void
                let verifiedSupervisorPin = null; // Store verified PIN for void operation
                let voidedOrderId = null; // Track order ID when all items are voided (for cancellation when starting new order)
                let supervisorPinContext = 'void'; // 'void' | 'status-revert' | 'delivery-override'
                let pendingStatusRevertIndex = null;
                let pendingDeliveryOverrideIndex = null;
                let pendingDeliveryOverrideAction = null;

                // Payment Modal Variables
                let selectedPaymentMethod = null;
                let paymentCashAmount = 0;
                let paymentCardAmount = 0;
                let activePaymentInput = 'cash'; // 'cash' or 'card'

                // Pork Ingredient Modal Variables
                let pendingPorkItem = null; // Stores the item waiting for ingredient selection
                let porkRecipes = []; // Stores fetched recipes for the modal

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

                    const hasUndeliveredItems = billItems.some(item => {
                        const status = (item?.status || '').toString().toLowerCase();
                        return status !== 'delivered';
                    });

                    if (hasUndeliveredItems) {
                        showNotification('Cannot close order until all items are Delivered', 'Action Not Allowed');
                        console.log('❌ Undelivered items found - aborting close order');
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

                    const vatRate = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                    const ssclRate = parseFloat('{{ $ssclRate ?? 0 }}') || 0;

                    let baseTotal = 0;
                    let ssclAmount = 0;
                    let vatAmount = 0;

                    billItems.forEach(item => {
                        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);

                        const taxInfo = itemTaxInfo[item.item_id] || { vat_available: false, sscl_available: false };
                        const appliesVat = taxInfo.vat_available ? vatRate : 0;
                        const appliesSscl = taxInfo.sscl_available ? ssclRate : 0;

                        const itemBaseTotal = itemTotal / ((1 + (appliesSscl / 100)) * (1 + (appliesVat / 100)));
                        const itemSsclAmount = itemBaseTotal * (appliesSscl / 100);
                        const itemVatAmount = (itemBaseTotal + itemSsclAmount) * (appliesVat / 100);

                        baseTotal += itemBaseTotal;
                        ssclAmount += itemSsclAmount;
                        vatAmount += itemVatAmount;
                    });

                    const subtotalBaseEl = document.getElementById('paymentSubtotalBase');
                    if (subtotalBaseEl) subtotalBaseEl.textContent = baseTotal.toFixed(2);

                    const ssclEl = document.getElementById('paymentSscl');
                    if (ssclEl) ssclEl.textContent = ssclAmount.toFixed(2);

                    const vatEl = document.getElementById('paymentVat');
                    if (vatEl) vatEl.textContent = vatAmount.toFixed(2);

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

                // IMPORTANT: Tell QZ Tray to use SHA512 - must match backend openssl_sign algorithm
                qz.security.setSignatureAlgorithm("SHA512");

                // Load the public certificate - CRITICAL: no leading whitespace inside the PEM block
                qz.security.setCertificatePromise(function (resolve, reject) {
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

                // Sign requests using backend PHP (SHA512 to match backend openssl_sign)
                qz.security.setSignaturePromise(function (toSign) {
                    return function (resolve, reject) {
                        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        var token = tokenMeta ? tokenMeta.content : '';

                        fetch('/qz/sign', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ data: toSign })
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (data) {
                                if (data.signature) {
                                    resolve(data.signature);
                                } else {
                                    console.error('QZ Signature Error:', data);
                                    reject(data.error || 'No signature returned from server');
                                }
                            })
                            .catch(function (err) {
                                console.error('QZ Signing request failed:', err);
                                reject(err);
                            });
                    };
                });

                console.log('QZ Tray: Running in SECURE mode (Backend Signing active)');

                // Auto-connect on page load to detect issues early
                qz.websocket.connect().then(function () {
                    console.log('QZ Tray: WebSocket connected successfully on page load');
                    return qz.printers.find();
                }).then(function (printers) {
                    console.log('QZ Tray: Available printers:', printers);
                }).catch(function (err) {
                    console.error('QZ Tray: Connection failed on page load:', err);
                });

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

                        // Debug: List all available printers to help user debug
                        try {
                            const printers = await qz.printers.find();
                            console.log("QZ Tray Available Printers:", printers);
                        } catch (e) { console.warn("Could not list printers", e); }


                        // 2. Select printer (Robust finding)
                        let printer;
                        if (printerName) {
                            try {
                                // Try to find specific printer (exact match)
                                printer = await qz.printers.find(printerName);
                            } catch (err) {
                                console.warn(`Printer '${printerName}' not found.`);
                                // Fallback will happen below
                            }
                        }

                        if (!printer) {
                            // If named printer not found or not specified, get default
                            try {
                                printer = await qz.printers.getDefault();
                                console.log(`Using default printer: ${printer}`);

                                if (printerName && showErrorOnFail) {
                                    // Warn user we switched to default because requested one wasn't found
                                    alert(`Printer '${printerName}' not found!\n\nUsing system default printer: ${printer}\n\nPlease rename your printer to '${printerName}' in Windows settings if you want to target it specifically.`);
                                }
                            } catch (err) {
                                throw new Error("No printers found. Please install a printer.");
                            }
                        }

                        // 3. Prepare print configuration for thermal printers
                        const config = qz.configs.create(printer, {
                            // PDF-specific settings
                            scaleContent: false,   // CRITICAL: Don't scale - print at actual size
                            rasterize: true,       // Rasterize for thermal printers
                            interpolation: 'nearest-neighbor', // Sharp text

                            // Paper size for 80mm thermal paper
                            units: 'mm',
                            size: {
                                width: 80          // 80mm thermal paper
                                // height: auto - determined by PDF content
                            },

                            // Zero margins - let the PDF content fill the paper
                            margins: {
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0
                            },

                            // Thermal printer specific
                            colorType: 'grayscale',
                            copies: 1
                        });

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

                        // FALLBACK: Auto-download the PDF
                        try {
                            const link = document.createElement('a');
                            link.href = 'data:application/pdf;base64,' + pdfBase64;
                            link.download = `${jobType.replace(/\s+/g, '_')}_${new Date().getTime()}.pdf`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            console.log('Fallback: Auto-downloaded PDF due to QZ Tray failure.');
                        } catch (downloadErr) {
                            console.error('Fallback download failed:', downloadErr);
                        }

                        if (showErrorOnFail) {
                            let errorMessage = 'Printing failed. Is QZ Tray running?\\n\\n' + err.message;
                            if (err.message && err.message.includes('default printer')) {
                                errorMessage = 'Printing failed. A default printer cannot be found. Please set it in the OS.';
                            } else if (err.message && err.message.includes('Failed to get signature')) {
                                errorMessage = 'Printing failed. Server signature error. Check the backend.';
                            }
                            alert(errorMessage + "\\n\\nThe document has been downloaded automatically.");
                        } else {
                            console.warn("Silent Print Failed (Ignored): " + err.message);
                        }

                        // Mutate the original error message to inform callers about the fallback
                        err.message = err.message + " (The receipt was downloaded automatically).";
                        throw err;
                    }
                }
                // --- END QZ TRAY CONFIGURATION ---

                /**
                 * Generate and Print KOT (Kitchen Order Ticket)
                 * @param {Array} foodItems - Array of food items to print
                 * @param {Object} orderInfo - Order information (order_number, table_number, kot_display_number, kot_sub_number, etc.)
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

                        // CRITICAL: Calculate TALL page height to fit all items with large fonts
                        // Base height: header (80mm) + order info (80mm) + footer (40mm) = 200mm
                        // Per item: 50mm (accounts for 16pt font name + possible word wrap + 18pt quantity + separator + spacing)
                        // Safety buffer: 80mm extra to ensure no content is ever cut off
                        const baseHeight = 200;
                        const perItemHeight = 50; // Very generous - accounts for 2-3 line item names
                        const safetyBuffer = 80; // Large buffer to prevent any content cutoff
                        const calculatedHeight = baseHeight + (foodItems.length * perItemHeight) + safetyBuffer;
                        const pageHeight = Math.max(300, calculatedHeight); // Minimum 300mm

                        // Debug log: Verify ALL items are being processed
                        console.log(`[KOT] Printing ${foodItems.length} items | Page Height: ${pageHeight}mm | No limit applied`);

                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'mm',
                            format: [80, pageHeight], // Dynamic height based on items
                            compress: false // Disable compression to prevent font scaling issues
                        });

                        let yPosition = 10;
                        const pageWidth = 80;
                        const leftMargin = 5;
                        const rightMargin = 5;

                        // Determine if this is an addition (sub_number > 0)
                        const isAddition = orderInfo.kot_sub_number && orderInfo.kot_sub_number > 0;

                        // Header
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(18);
                        pdf.text('KITCHEN ORDER TICKET', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        pdf.setFontSize(14);
                        // Show (KOT) or (KOT - ADDITION) for sub-KOTs
                        const kotLabel = isAddition ? '(KOT - ADDITION #' + orderInfo.kot_sub_number + ')' : '(KOT)';
                        pdf.text(kotLabel, pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 10;

                        // Restaurant Info
                        pdf.setFontSize(11);
                        pdf.text('RAVON BAKERS', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        // Separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // Order Information
                        pdf.setFontSize(11);
                        pdf.setFont('courier', 'bold');
                        pdf.text('KOT NO:', leftMargin, yPosition);
                        // Use kot_display_number which includes sub-number if applicable
                        const kotNumber = orderInfo.kot_display_number || orderInfo.kot_number || orderInfo.order_number || 'N/A';
                        pdf.text(String(kotNumber), pageWidth - rightMargin, yPosition, {
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
                        yPosition += 6;

                        // Items separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // Print Items - LARGE FONT for kitchen readability
                        foodItems.forEach((item, index) => {
                            pdf.setFont('courier', 'bold');
                            pdf.setFontSize(16); // INCREASED from 12 for better readability

                            let itemName = item.name || item.item_name;

                            // Word wrap for long item names
                            const maxWidth = pageWidth - leftMargin - rightMargin;
                            const lines = pdf.splitTextToSize(itemName, maxWidth);

                            lines.forEach(line => {
                                pdf.text(line, leftMargin, yPosition);
                                yPosition += 7; // Increased line spacing
                            });

                            // Quantity - LARGE and BOLD
                            pdf.setFontSize(18); // INCREASED from 14 for visibility
                            pdf.text(`x ${item.quantity}`, leftMargin + 2, yPosition);
                            yPosition += 8; // Increased spacing

                            // Add spacing between items
                            if (index < foodItems.length - 1) {
                                pdf.setLineDashPattern([0.5, 0.5], 0);
                                pdf.setLineWidth(0.3);
                                pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                                pdf.setLineDashPattern([], 0);
                                yPosition += 5; // Increased spacing between items
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

                        // DEBUG: Check if content exceeds page height
                        console.log(`KOT Final Y Position: ${yPosition}mm, Page Height: ${pageHeight}mm`);
                        if (yPosition > pageHeight - 10) {
                            console.warn(`WARNING: Content overflows! yPosition (${yPosition}) > pageHeight (${pageHeight}) - 10`);
                        }

                        // Generate Base64 and Print directly to thermal printer
                        const pdfBase64 = pdf.output('datauristring').split(',')[1];

                        // Use configured printer or default printer
                        const kotPrinterName = "OutletPOS";
                        await printPDFwithQZ(pdfBase64, kotPrinterName, "KOT", false);
                        console.log('KOT sent to thermal printer successfully');

                    } catch (error) {
                        console.error('KOT Generation Error:', error);
                    }
                }

                /**
                 * Generate and Print BOT (Bar Order Ticket)
                 * @param {Array} beverageItems - Array of beverage items to print
                 * @param {Object} orderInfo - Order information (order_number, table_number, bot_display_number, bot_sub_number, etc.)
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

                        // CRITICAL: Calculate TALL page height to fit all items with large fonts
                        // Base height: header (80mm) + order info (80mm) + footer (40mm) = 200mm
                        // Per item: 50mm (accounts for 16pt font name + possible word wrap + 18pt quantity + separator + spacing)
                        // Safety buffer: 80mm extra to ensure no content is ever cut off
                        const baseHeight = 200;
                        const perItemHeight = 50; // Very generous - accounts for 2-3 line item names
                        const safetyBuffer = 80; // Large buffer to prevent any content cutoff
                        const calculatedHeight = baseHeight + (beverageItems.length * perItemHeight) + safetyBuffer;
                        const pageHeight = Math.max(300, calculatedHeight); // Minimum 300mm

                        // Debug log: Verify ALL items are being processed
                        console.log(`[BOT] Printing ${beverageItems.length} items | Page Height: ${pageHeight}mm | No limit applied`);

                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'mm',
                            format: [80, pageHeight], // Dynamic height based on items
                            compress: false // Disable compression to prevent font scaling issues
                        });

                        let yPosition = 10;
                        const pageWidth = 80;
                        const leftMargin = 5;
                        const rightMargin = 5;

                        // Determine if this is an addition (sub_number > 0)
                        const isAddition = orderInfo.bot_sub_number && orderInfo.bot_sub_number > 0;

                        // Header
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(18);
                        pdf.text('BAR ORDER TICKET', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        pdf.setFontSize(14);
                        // Show (BOT) or (BOT - ADDITION) for sub-BOTs
                        const botLabel = isAddition ? '(BOT - ADDITION #' + orderInfo.bot_sub_number + ')' : '(BOT)';
                        pdf.text(botLabel, pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 10;

                        // Restaurant Info
                        pdf.setFontSize(11);
                        pdf.text('RAVON BAKERS', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        // Separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // Order Information
                        pdf.setFontSize(11);
                        pdf.setFont('courier', 'bold');
                        pdf.text('BOT NO:', leftMargin, yPosition);
                        // Use bot_display_number which includes sub-number if applicable
                        const botNumber = orderInfo.bot_display_number || orderInfo.bot_number || orderInfo.order_number || 'N/A';
                        pdf.text(String(botNumber), pageWidth - rightMargin, yPosition, {
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
                        yPosition += 6;

                        // Items separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // Print Items - LARGE FONT for bar readability
                        beverageItems.forEach((item, index) => {
                            pdf.setFont('courier', 'bold');
                            pdf.setFontSize(16); // INCREASED from 12 for better readability

                            let itemName = item.name || item.item_name;

                            // Word wrap for long item names
                            const maxWidth = pageWidth - leftMargin - rightMargin;
                            const lines = pdf.splitTextToSize(itemName, maxWidth);

                            lines.forEach(line => {
                                pdf.text(line, leftMargin, yPosition);
                                yPosition += 7; // Increased line spacing
                            });

                            // Quantity - LARGE and BOLD
                            pdf.setFontSize(18); // INCREASED from 14 for visibility
                            pdf.text(`x ${item.quantity}`, leftMargin + 2, yPosition);
                            yPosition += 8; // Increased spacing

                            // Add spacing between items
                            if (index < beverageItems.length - 1) {
                                pdf.setLineDashPattern([0.5, 0.5], 0);
                                pdf.setLineWidth(0.3);
                                pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                                pdf.setLineDashPattern([], 0);
                                yPosition += 5; // Increased spacing between items
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

                        // Generate Base64 and Print directly to thermal printer
                        const pdfBase64 = pdf.output('datauristring').split(',')[1];

                        // Use configured printer or default printer
                        const botPrinterName = "BOT";
                        await printPDFwithQZ(pdfBase64, botPrinterName, "BOT", false);
                        console.log('BOT sent to thermal printer successfully');

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
                function addItemToBill(itemId, itemName, itemPrice, excludedIngredients) {
                    // If user adds an item after voiding all items, they're continuing the order
                    // Clear the voided tracking so the order won't be cancelled
                    if (voidedOrderId && currentOrderId === voidedOrderId) {
                        voidedOrderId = null;
                    }

                    // Build excluded names suffix for display
                    let displayName = itemName;
                    if (excludedIngredients && excludedIngredients.length > 0) {
                        const excludedNames = excludedIngredients.map(e => 'NO ' + e.name).join(', ');
                        displayName = `${itemName} [${excludedNames}]`;
                    }

                    // Items with exclusions are always unique entries
                    if (!excludedIngredients || excludedIngredients.length === 0) {
                        const existingItem = billItems.find(item => item.item_id === itemId && item.modifier_id === null && item.name === itemName && !item.excluded_ingredients);
                        if (existingItem) {
                            existingItem.quantity++;
                            renderBill();
                            calculateTotals();
                            return;
                        }
                    }

                    billItems.push({
                        item_id: itemId,
                        modifier_id: null, // No modifier for items without portions
                        name: displayName,
                        price: itemPrice,
                        quantity: 1,
                        delivered_quantity: 0,
                        delivery_selected_quantity: 0,
                        showDeliveryControls: false,
                        isSupervisorMode: false,
                        supervisorActionType: null,
                        modifiers: [],
                        excluded_ingredients: excludedIngredients || null
                    });

                    renderBill();
                    calculateTotals();
                }

                // Render bill items
                function getDeliveryState(item) {
                    const totalQuantity = Math.max(parseInt(item.quantity) || 0, 0);
                    const deliveredQuantity = Math.min(Math.max(parseInt(item.delivered_quantity) || 0, 0), totalQuantity);
                    const remainingQuantity = Math.max(totalQuantity - deliveredQuantity, 0);

                    return {
                        total_quantity: totalQuantity,
                        delivered_quantity: deliveredQuantity,
                        remaining_quantity: remainingQuantity,
                        showDeliveryControls: !!item.showDeliveryControls && remainingQuantity > 0
                    };
                }

                function normalizeDeliverySelection(item) {
                    const deliveryState = getDeliveryState(item);
                    const currentSelection = Math.max(parseInt(item.delivery_selected_quantity) || 0, 0);
                    const inputMax = item.isSupervisorMode ? deliveryState.total_quantity : deliveryState.remaining_quantity;
                    item.delivery_selected_quantity = Math.min(currentSelection, inputMax);
                    return deliveryState;
                }

                function showBillItemDeliveryControls(index) {
                    const item = billItems[index];
                    if (!item || !item.id || !currentOrderId) {
                        return;
                    }

                    const deliveryState = normalizeDeliverySelection(item);
                    if (item.status === 'delivered' || deliveryState.remaining_quantity === 0) {
                        item.showDeliveryControls = false;
                        renderBill();
                        return;
                    }

                    item.showDeliveryControls = true;
                    item.isSupervisorMode = false;
                    item.delivery_selected_quantity = deliveryState.remaining_quantity;
                    renderBill();
                }

                function requestDeliverySupervisorOverride(index, actionType = 'correction') {
                    const item = billItems[index];
                    if (!item || !item.id || !currentOrderId) {
                        return;
                    }

                    supervisorPinContext = 'delivery-override';
                    pendingDeliveryOverrideIndex = index;
                    pendingDeliveryOverrideAction = actionType;

                    document.getElementById('supervisorPinInput').value = '';
                    document.getElementById('supervisorPinError').classList.add('hidden');
                    verifiedSupervisorPin = null;

                    document.getElementById('supervisorPinModal').classList.remove('hidden');
                    setTimeout(() => {
                        document.getElementById('supervisorPinInput').focus();
                    }, 100);
                }

                function enableSupervisorDeliveryMode(index, actionType) {
                    const item = billItems[index];
                    if (!item) {
                        return;
                    }

                    const deliveryState = getDeliveryState(item);
                    item.isSupervisorMode = true;
                    item.supervisorActionType = actionType;
                    item.showDeliveryControls = true;
                    item.delivery_selected_quantity = deliveryState.delivered_quantity;
                    if (actionType === 'reopen' && deliveryState.delivered_quantity > 0) {
                        item.delivery_selected_quantity = Math.max(deliveryState.delivered_quantity - 1, 0);
                    }
                    renderBill();
                }

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

                    billItemsDiv.innerHTML = billItems.map((item, index) => {
                        const originalItem = originalOrderItems.find(o =>
                            o.item_id === item.item_id && o.modifier_id === item.modifier_id && o.name === item.name
                        );
                        const isOriginalItem = !!originalItem;
                        const originalQty = originalItem ? originalItem.quantity : 0;

                        const deliveryState = normalizeDeliverySelection(item);
                        const isFullyDelivered = item.status === 'delivered' && deliveryState.remaining_quantity === 0;

                        const canDecrement = !isFullyDelivered && (!isOriginalItem || item.quantity > originalQty);
                        const canIncrement = true;

                        const decrementBtnClass = canDecrement
                            ? 'w-6 h-6 bg-red-600 text-white rounded hover:bg-red-700 cursor-pointer'
                            : 'w-6 h-6 bg-gray-600 text-gray-400 rounded cursor-not-allowed opacity-50';

                        const decrementDisabledReason = isFullyDelivered
                            ? 'Fully delivered items cannot be changed'
                            : 'Use VOID to reduce previously ordered items';

                        const decrementOnclick = canDecrement
                            ? `onclick="decrementQuantity(${index})"`
                            : `disabled title="${decrementDisabledReason}"`;

                        const incrementButtonClass = canIncrement
                            ? 'w-6 h-6 bg-green-600 text-white rounded hover:bg-green-700 cursor-pointer'
                            : 'w-6 h-6 bg-gray-600 text-gray-400 rounded cursor-not-allowed opacity-50';
                        const incrementOnclick = canIncrement
                            ? `onclick="incrementQuantity(${index})"`
                            : 'disabled';

                        const canShowDeliveryControls = !!(currentOrderId && item.id && deliveryState.remaining_quantity > 0 && item.status !== 'delivered');
                        const canReopenDelivered = !!(currentOrderId && item.id && isFullyDelivered);
                        const statusBadgeText = isFullyDelivered ? 'Delivered' : 'Preparing';
                        const statusBadgeClass = isFullyDelivered
                            ? 'bg-emerald-600/20 text-emerald-300 border border-emerald-600/40 cursor-pointer'
                            : (canShowDeliveryControls
                                ? 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/40 cursor-pointer hover:bg-yellow-500/30'
                                : 'bg-gray-600/40 text-gray-300 border border-gray-500/50 cursor-not-allowed');

                        const showInlineDelivery = item.showDeliveryControls && (canShowDeliveryControls || item.isSupervisorMode || canReopenDelivered);
                        const deliveryInputMax = item.isSupervisorMode ? deliveryState.total_quantity : deliveryState.remaining_quantity;
                        const deliveryInputValue = Math.min(item.delivery_selected_quantity || 0, deliveryInputMax);
                        const canDecrementDelivery = deliveryInputValue > 0;
                        const canIncrementDelivery = deliveryInputValue < deliveryInputMax;
                        const isPlusDisabled = !canIncrementDelivery;
                        const canSubmitDelivery = item.isSupervisorMode
                            ? deliveryInputValue !== deliveryState.delivered_quantity
                            : deliveryInputValue > 0;
                        const deliveryButtonText = item.isSupervisorMode ? 'Update' : 'Deliver';
                        const deliveryHelpText = item.isSupervisorMode
                            ? `Delivered: ${deliveryInputValue} / ${deliveryState.total_quantity}`
                            : `Remaining: ${deliveryState.remaining_quantity}`;

                        const deliveryControlsHtml = showInlineDelivery ? `
                                                                                                                                                                                                                                                        <div class="mt-2 transition-opacity duration-200 ease-in-out">
                                                                                                                                                                                                                                                            <div class="flex items-center justify-center gap-2">
                                                                                                                                                                                                                                                                <div class="flex items-center space-x-2">
                                                                                                                                                                                                                                                                    <button onclick="decrementDeliveryQuantity(${index})" ${canDecrementDelivery ? '' : 'disabled'}
                                                                                                                                                                                                                                                                        class="w-6 h-6 rounded ${canDecrementDelivery ? 'bg-red-600 text-white hover:bg-red-700 cursor-pointer' : 'bg-gray-600 text-gray-400 cursor-not-allowed opacity-50'}">-</button>
                                                                                                                                                                                                                                                                    <span class="text-white font-semibold min-w-[1.25rem] text-center">${deliveryInputValue}</span>
                                                                                                                                                                                                                                                                    <button onclick="handleDeliveryPlusClick(${index}, ${canIncrementDelivery ? 'true' : 'false'})" ondblclick="handleDeliveryPlusDoubleClick(${index}, ${canIncrementDelivery ? 'true' : 'false'})" title="${isPlusDisabled ? 'Double click for supervisor correction' : ''}"
                                                                                                                                                                                                                                                                        class="w-6 h-6 rounded ${canIncrementDelivery ? 'bg-green-600 text-white hover:bg-green-700 cursor-pointer' : 'bg-gray-600 text-gray-400 cursor-not-allowed opacity-50'}">+</button>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                <button onclick="deliverBillItem(${index})" ${canSubmitDelivery ? '' : 'disabled'}
                                                                                                                                                                                                                                                                    class="text-xs px-2 py-1 rounded font-semibold transition ${canSubmitDelivery ? 'bg-blue-600 text-white hover:bg-blue-700 cursor-pointer' : 'bg-gray-600 text-gray-400 cursor-not-allowed'}">
                                                                                                                                                                                                                                                                    ${deliveryButtonText}
                                                                                                                                                                                                                                                                </button>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                            <div class="text-[11px] text-blue-200 mt-1 text-center">${deliveryHelpText}</div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    ` : '';

                        return `
                                                                                                                                                                                                                                            <div class="bg-gray-700 rounded-lg p-3 border border-gray-600 ${isOriginalItem && item.quantity <= originalQty ? 'border-l-4 border-l-orange-500' : ''}">
                                                                                                                                                                                                                                                <div class="grid grid-cols-3 gap-2 text-sm">
                                                                                                                                                                                                                                                    <div class="col-span-2">
                                                                                                                                                                                                                                                        <div class="font-semibold text-white">${index + 1}. ${item.name}</div>
                                                                                                                                                                                                                                                        <div class="text-xs text-gray-400">Rs. ${item.price.toFixed(2)} each</div>
                                                                                                                                                                                                                                                        <div class="mt-1 flex items-center gap-2">
                                                                                                                                                                                                                                                            <span ${(canShowDeliveryControls || canReopenDelivered) ? `onclick="showBillItemDeliveryControls(${index})"` : ''} ${canReopenDelivered ? `ondblclick="requestDeliverySupervisorOverride(${index}, 'reopen')" title="Double click to reopen with supervisor"` : ''}
                                                                                                                                                                                                                                                                class="text-xs px-2 py-1 rounded-full ${statusBadgeClass} select-none">
                                                                                                                                                                                                                                                                ${statusBadgeText}
                                                                                                                                                                                                                                                            </span>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                        ${isOriginalItem && item.quantity <= originalQty ? '<div class="text-xs text-orange-400 mt-1"></div>' : ''}
                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                    <div class="text-center">
                                                                                                                                                                                                                                                        <div class="flex items-center justify-center space-x-2">
                                                                                                                                                                                                                                                            <button ${decrementOnclick} class="${decrementBtnClass}">-</button>
                                                                                                                                                                                                                                                            <span class="text-white font-semibold">${item.quantity}</span>
                                                                                                                                                                                                                                                            <button ${incrementOnclick} class="${incrementButtonClass}">+</button>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                        ${deliveryControlsHtml}
                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                <div class="text-right mt-2 text-white font-semibold">
                                                                                                                                                                                                                                                    Rs. ${(item.price * item.quantity).toFixed(2)}
                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                        `
                    }).join('');
                }

                // Increment/Decrement quantity
                function incrementQuantity(index) {
                    billItems[index].quantity++;
                    renderBill();
                    calculateTotals();
                }

                function decrementQuantity(index) {
                    const item = billItems[index];

                    const originalItem = originalOrderItems.find(o =>
                        o.item_id === item.item_id && o.modifier_id === item.modifier_id && o.name === item.name
                    );

                    if (originalItem) {
                        if (item.quantity > originalItem.quantity) {
                            item.quantity--;
                            renderBill();
                            calculateTotals();
                        } else {
                            showNotification('Use the VOID button to reduce previously ordered items', 'Cannot Reduce');
                        }
                    } else {
                        if (item.quantity > 1) {
                            item.quantity--;
                        } else {
                            billItems.splice(index, 1);
                        }
                        renderBill();
                        calculateTotals();
                    }
                }

                function incrementDeliveryQuantity(index) {
                    const item = billItems[index];
                    if (!item) {
                        return;
                    }

                    const deliveryState = normalizeDeliverySelection(item);
                    const deliveryInputMax = item.isSupervisorMode ? deliveryState.total_quantity : deliveryState.remaining_quantity;
                    if (deliveryInputMax === 0) {
                        item.delivery_selected_quantity = 0;
                        renderBill();
                        return;
                    }

                    item.delivery_selected_quantity = Math.min((item.delivery_selected_quantity || 0) + 1, deliveryInputMax);
                    renderBill();
                }

                function handleDeliveryPlusClick(index, canIncrementDelivery) {
                    if (!canIncrementDelivery) {
                        return;
                    }

                    incrementDeliveryQuantity(index);
                }

                function handleDeliveryPlusDoubleClick(index, canIncrementDelivery) {
                    if (canIncrementDelivery) {
                        return;
                    }

                    const item = billItems[index];
                    if (!item || !item.id || item.isSupervisorMode) {
                        return;
                    }

                    requestDeliverySupervisorOverride(index, 'correction');
                }

                function decrementDeliveryQuantity(index) {
                    const item = billItems[index];
                    if (!item) {
                        return;
                    }

                    normalizeDeliverySelection(item);
                    item.delivery_selected_quantity = Math.max((item.delivery_selected_quantity || 0) - 1, 0);
                    renderBill();
                }

                // Calculate totals
                function calculateTotals() {
                    const vatRate = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                    const ssclRate = parseFloat('{{ $ssclRate ?? 0 }}') || 0;

                    const grandTotal = billItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);

                    let baseTotal = 0;
                    let ssclAmount = 0;
                    let vatAmount = 0;

                    billItems.forEach(item => {
                        const itemTotal = parseFloat(item.price) * parseInt(item.quantity);

                        const taxInfo = itemTaxInfo[item.item_id] || { vat_available: false, sscl_available: false };
                        const appliesVat = taxInfo.vat_available ? vatRate : 0;
                        const appliesSscl = taxInfo.sscl_available ? ssclRate : 0;

                        const itemBaseTotal = itemTotal / ((1 + (appliesSscl / 100)) * (1 + (appliesVat / 100)));
                        const itemSsclAmount = itemBaseTotal * (appliesSscl / 100);
                        const itemVatAmount = (itemBaseTotal + itemSsclAmount) * (appliesVat / 100);

                        baseTotal += itemBaseTotal;
                        ssclAmount += itemSsclAmount;
                        vatAmount += itemVatAmount;
                    });

                    // Update UI elements
                    const totalEl = document.getElementById('total');
                    if (totalEl) totalEl.textContent = grandTotal.toFixed(2);

                    const subTotalBaseEl = document.getElementById('subTotalBase');
                    if (subTotalBaseEl) subTotalBaseEl.textContent = baseTotal.toFixed(2);

                    const ssclAmountEl = document.getElementById('ssclAmount');
                    if (ssclAmountEl) ssclAmountEl.textContent = ssclAmount.toFixed(2);

                    const vatAmountEl = document.getElementById('vatAmount');
                    if (vatAmountEl) vatAmountEl.textContent = vatAmount.toFixed(2);
                }

                // Cancel a voided order (when user starts a new order without adding items)
                async function cancelVoidedOrderIfExists() {
                    if (!voidedOrderId) return; // No voided order to cancel

                    try {
                        const response = await fetch('{{ route("pos.cancelVoidedOrder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                order_id: voidedOrderId
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            console.log('Voided order cancelled:', voidedOrderId);
                        } else {
                            console.error('Failed to cancel voided order:', result.message);
                        }
                    } catch (error) {
                        console.error('Error cancelling voided order:', error);
                    }

                    // Clear tracking variables
                    voidedOrderId = null;
                    currentOrderId = null;
                    originalOrderItems = [];
                    billItems = [];
                    printedItems = [];
                }

                // Set Order Type Helper
                async function setOrderType(type, label) {
                    // Cancel any voided order before starting a new one
                    await cancelVoidedOrderIfExists();

                    currentOrderType = type;
                    selectedTableId = null;
                    currentOrderId = null;
                    voidedOrderId = null; // Reset voided order tracking
                    const display = document.getElementById('orderTypeDisplay');
                    if (display) display.textContent = label;

                    document.getElementById('menuSelectionContainer').classList.remove('hidden');
                    document.getElementById('menuSelectionContainer').classList.add('flex');
                    const msg = document.getElementById('initialStateMessage');
                    if (msg) msg.classList.add('hidden');
                }

                // Select Item (Check for Sub-items/Portions)
                function selectItem(itemId, itemName, itemPrice, modifiers, pickmePrice, porkAvailable) {
                    // Determine the price to use based on order type
                    let priceToUse = itemPrice;
                    if (currentOrderType === 'pickme' && pickmePrice !== null && pickmePrice !== undefined) {
                        priceToUse = pickmePrice;
                    }

                    const portionModifiers = modifiers.filter(m => {
                        const name = m.name.toLowerCase();
                        const type = (m.type || '').toLowerCase();

                        return type === 'size' || type === 'portion' ||
                            name.includes('large') || name.includes('small') || name.includes('regular') ||
                            name.includes('ml') || name.includes('liter') || name.includes(' l');
                    });

                    if (portionModifiers.length > 0) {
                        showPortionSelection(itemId, itemName, priceToUse, portionModifiers, porkAvailable);
                    } else {
                        // Item without portions
                        if (porkAvailable) {
                            showPorkModal(itemId, itemName, priceToUse, null, null);
                        } else {
                            addItemToBill(itemId, itemName, priceToUse);
                        }
                        clearPortionSelection();
                    }
                }

                function showPortionSelection(itemId, itemName, basePrice, portions, itemPorkAvailable) {
                    const optionsDiv = document.getElementById('portionOptions');
                    const closeBtn = document.getElementById('closePortionBtn');

                    optionsDiv.innerHTML = portions.map(p => {
                        // Determine price to use based on order type
                        let portionPrice = p.price_adjustment;
                        if (currentOrderType === 'pickme' && p.pickme_price !== null && p.pickme_price !== undefined) {
                            portionPrice = p.pickme_price;
                        }

                        // Check pork availability: modifier-level takes precedence, fall back to item-level
                        const hasPork = p.pork_available || itemPorkAvailable;

                        return `
                                                                                                                                                                                                                                            <button class="p-3 bg-blue-700 text-white rounded-lg hover:bg-blue-600 transition font-semibold"
                                                                                                                                                                                                                                                    onclick="addPortionToBill(${itemId}, '${itemName}', ${portionPrice}, '${p.name}', ${p.id}, ${hasPork ? 'true' : 'false'})">
                                                                                                                                                                                                                                                ${p.name}
                                                                                                                                                                                                                                            </button>
                                                                                                                                                                                                                                        `;
                    }).join('');

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

                function addPortionToBill(itemId, itemName, portionPrice, portionName, modifierId, hasPork) {
                    // If pork_available, show ingredient modal before adding
                    if (hasPork) {
                        showPorkModal(itemId, itemName, portionPrice, portionName, modifierId);
                        return;
                    }

                    addPortionToBillDirect(itemId, itemName, portionPrice, portionName, modifierId);
                }

                function addPortionToBillDirect(itemId, itemName, portionPrice, portionName, modifierId, excludedIngredients) {
                    // If user adds an item after voiding all items, they're continuing the order
                    // Clear the voided tracking so the order won't be cancelled
                    if (voidedOrderId && currentOrderId === voidedOrderId) {
                        voidedOrderId = null;
                    }

                    const fullName = `${itemName} (${portionName})`;

                    // Build excluded names suffix for display
                    let displayName = fullName;
                    if (excludedIngredients && excludedIngredients.length > 0) {
                        const excludedNames = excludedIngredients.map(e => 'NO ' + e.name).join(', ');
                        displayName = `${fullName} [${excludedNames}]`;
                    }

                    // Items with exclusions are always unique entries
                    if (!excludedIngredients || excludedIngredients.length === 0) {
                        const existingItem = billItems.find(item => item.item_id === itemId && item.modifier_id === modifierId && item.name === fullName && !item.excluded_ingredients);
                        if (existingItem) {
                            existingItem.quantity++;
                            renderBill();
                            calculateTotals();
                            clearPortionSelection();
                            return;
                        }
                    }

                    billItems.push({
                        item_id: itemId,
                        modifier_id: modifierId,
                        name: displayName,
                        price: portionPrice,
                        quantity: 1,
                        delivered_quantity: 0,
                        delivery_selected_quantity: 0,
                        showDeliveryControls: false,
                        isSupervisorMode: false,
                        supervisorActionType: null,
                        modifiers: [],
                        excluded_ingredients: excludedIngredients || null
                    });

                    renderBill();
                    calculateTotals();
                    clearPortionSelection();
                }

                // === PORK INGREDIENT MODAL FUNCTIONS ===
                async function showPorkModal(itemId, itemName, price, portionName, modifierId) {
                    // Store pending item info
                    pendingPorkItem = {
                        itemId: itemId,
                        itemName: itemName,
                        price: price,
                        portionName: portionName,
                        modifierId: modifierId
                    };

                    const displayName = portionName ? `${itemName} (${portionName})` : itemName;
                    document.getElementById('porkModalItemName').textContent = displayName;

                    // Show modal and loading spinner
                    document.getElementById('porkIngredientModal').classList.remove('hidden');
                    document.getElementById('porkLoadingSpinner').classList.remove('hidden');
                    document.getElementById('porkIngredientsList').innerHTML = '';

                    try {
                        // Fetch recipes/ingredients from API
                        const params = new URLSearchParams({
                            item_id: itemId
                        });
                        if (modifierId) {
                            params.append('modifier_id', modifierId);
                        }

                        const response = await fetch(`{{ route('pos.itemRecipes') }}?${params.toString()}`);
                        const result = await response.json();

                        if (result.success && result.recipes.length > 0) {
                            porkRecipes = result.recipes;
                            renderPorkIngredients(result.recipes);
                        } else {
                            // No recipes found, just add the item directly
                            closePorkModal();
                            if (portionName) {
                                addPortionToBillDirect(itemId, itemName, price, portionName, modifierId);
                            } else {
                                addItemToBill(itemId, itemName, price);
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching recipes:', error);
                        closePorkModal();
                        // On error, add item without exclusions
                        if (portionName) {
                            addPortionToBillDirect(itemId, itemName, price, portionName, modifierId);
                        } else {
                            addItemToBill(itemId, itemName, price);
                        }
                    }

                    document.getElementById('porkLoadingSpinner').classList.add('hidden');
                }

                function renderPorkIngredients(recipes) {
                    const listDiv = document.getElementById('porkIngredientsList');
                    listDiv.innerHTML = recipes.map((recipe, idx) => `
                                                                        <label class="flex items-center gap-3 p-3 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition">
                                                                            <input type="checkbox" checked
                                                                                id="porkIngredient_${idx}"
                                                                                data-recipe-id="${recipe.id}"
                                                                                data-ingredient-id="${recipe.main_stock_item_id}"
                                                                                data-ingredient-name="${recipe.ingredient_name}"
                                                                                class="w-5 h-5 rounded text-pink-500 bg-gray-600 border-gray-500 focus:ring-pink-500">
                                                                            <span class="text-white flex-1">${recipe.ingredient_name}</span>
                                                                            <span class="text-gray-400 text-sm">${recipe.quantity} ${recipe.unit}</span>
                                                                        </label>
                                                                    `).join('');
                }

                function closePorkModal() {
                    document.getElementById('porkIngredientModal').classList.add('hidden');
                    pendingPorkItem = null;
                    porkRecipes = [];
                }

                function confirmPorkSelection() {
                    if (!pendingPorkItem) return;

                    // Collect unchecked (excluded) ingredients
                    const excludedIngredients = [];
                    const checkboxes = document.querySelectorAll('#porkIngredientsList input[type="checkbox"]');
                    checkboxes.forEach(cb => {
                        if (!cb.checked) {
                            excludedIngredients.push({
                                id: parseInt(cb.dataset.ingredientId),
                                recipe_id: parseInt(cb.dataset.recipeId),
                                name: cb.dataset.ingredientName
                            });
                        }
                    });

                    const { itemId, itemName, price, portionName, modifierId } = pendingPorkItem;

                    closePorkModal();

                    // Add to bill with exclusions (or without if none were excluded)
                    if (portionName) {
                        addPortionToBillDirect(itemId, itemName, price, portionName, modifierId,
                            excludedIngredients.length > 0 ? excludedIngredients : null);
                    } else {
                        addItemToBill(itemId, itemName, price,
                            excludedIngredients.length > 0 ? excludedIngredients : null);
                    }
                }
                // === END PORK INGREDIENT MODAL FUNCTIONS ===

                function openTakeAwayModal() {
                    setOrderType('takeaway', 'Take Away');
                    // Update Transfer Table button visibility (hide for non-dine-in orders)
                    updateTransferTableButtonVisibility();
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

                    // Update Transfer Table button visibility (hide for PickMe orders)
                    updateTransferTableButtonVisibility();
                }

                async function selectTable(tableNumber, tableId) {
                    // Cancel any voided order before starting a new one
                    await cancelVoidedOrderIfExists();

                    selectedTableId = tableId;
                    currentOrderType = 'dine_in';
                    currentOrderId = null;
                    voidedOrderId = null; // Reset voided order tracking
                    const display = document.getElementById('orderTypeDisplay');
                    if (display) display.textContent = 'Table: ' + tableNumber;

                    document.getElementById('menuSelectionContainer').classList.remove('hidden');
                    document.getElementById('menuSelectionContainer').classList.add('flex');
                    const msg = document.getElementById('initialStateMessage');
                    if (msg) msg.classList.add('hidden');

                    document.getElementById('tableModal').classList.add('hidden');

                    // Update Transfer Table button visibility
                    updateTransferTableButtonVisibility();
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.add('hidden');
                }

                // Checkout - Place Order
                let isPlacingOrder = false; // Flag to prevent double-clicking

                async function checkout() {
                    // Prevent double-clicking
                    if (isPlacingOrder) {
                        console.log('Order already being placed, ignoring click');
                        return;
                    }

                    if (billItems.length === 0) {
                        showNotification('Please add items to the bill first', 'Empty Bill');
                        return;
                    }

                    if (!currentOrderType) {
                        showNotification('Please select an order type first', 'Order Type Required');
                        return;
                    }

                    // Set flag and disable button
                    isPlacingOrder = true;
                    const placeOrderBtn = document.getElementById('placeOrderBtn');
                    const placeOrderBtnText = document.getElementById('placeOrderBtnText');
                    if (placeOrderBtn) {
                        placeOrderBtn.disabled = true;
                        placeOrderBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    if (placeOrderBtnText) {
                        placeOrderBtnText.textContent = 'Processing...';
                    }

                    try {
                        // Merge duplicate items before sending (use item_id + modifier_id for accurate matching)
                        const mergedItems = {};
                        billItems.forEach(item => {
                            // Use modifier_id in key for accurate item matching
                            // Use explicit null/undefined check to preserve modifier_id=0 (though unlikely)
                            const modId = (item.modifier_id !== null && item.modifier_id !== undefined) ? item.modifier_id : 'null';
                            // Include excluded_ingredients in key so items with different exclusions don't merge
                            const excludedKey = item.excluded_ingredients ? JSON.stringify(item.excluded_ingredients.map(e => e.id).sort()) : 'none';
                            const key = item.item_id + '_' + modId + '_' + item.name + '_' + excludedKey;
                            if (mergedItems[key]) {
                                // Item exists, add quantities
                                mergedItems[key].quantity += item.quantity;
                            } else {
                                // New item, add to merged list
                                mergedItems[key] = {
                                    item_id: item.item_id,
                                    modifier_id: (item.modifier_id !== null && item.modifier_id !== undefined) ? item.modifier_id : null,
                                    name: item.name,
                                    price: item.price,
                                    quantity: item.quantity,
                                    excluded_ingredients: item.excluded_ingredients ? item.excluded_ingredients.map(e => e.id) : null
                                };
                            }
                        });

                        // Convert merged items object to array
                        const itemsToSend = Object.values(mergedItems);

                        // Debug: log items being sent
                        console.log('Items being sent to backend:', itemsToSend.map(i => ({
                            item_id: i.item_id,
                            modifier_id: i.modifier_id,
                            name: i.name,
                            quantity: i.quantity
                        })));

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
                                    kot_sub_number: result.kot_sub_number || 0,
                                    kot_display_number: String(result.kot_display_number || result.kot_number || 'N/A'),
                                    bot_number: String(result.bot_number || 'N/A'),
                                    bot_sub_number: result.bot_sub_number || 0,
                                    bot_display_number: String(result.bot_display_number || result.bot_number || 'N/A'),
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
                    } finally {
                        // Reset flag and button state
                        isPlacingOrder = false;
                        const placeOrderBtn = document.getElementById('placeOrderBtn');
                        const placeOrderBtnText = document.getElementById('placeOrderBtnText');
                        if (placeOrderBtn) {
                            placeOrderBtn.disabled = false;
                            placeOrderBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                        if (placeOrderBtnText) {
                            placeOrderBtnText.textContent = 'Place Order';
                        }
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
                        // Cancel any voided order before opening Open Checks
                        // (if user is abandoning a voided order to select another)
                        await cancelVoidedOrderIfExists();

                        const response = await fetch('{{ route("pos.openChecks") }}');

                        // Check for session/authentication issues
                        if (response.status === 401 || response.status === 419) {
                            showNotification('Your session has expired. Please login again.', 'Session Expired');
                            setTimeout(() => {
                                window.location.href = '{{ route("login") }}';
                            }, 2000);
                            return;
                        }

                        // Check if response is not OK
                        if (!response.ok) {
                            showNotification('Error loading open checks. Status: ' + response.status, 'Error');
                            return;
                        }

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
                                container.innerHTML = result.orders.map(order => {
                                    let typeDisplay = '';
                                    if (order.order_type === 'dine_in' && order.table_number !== 'N/A') {
                                        typeDisplay = `Table: ${order.table_number}`;
                                    } else if (order.order_type === 'takeaway') {
                                        typeDisplay = 'TakeAway';
                                    } else if (order.order_type === 'pickme' && order.pickme_ref_number) {
                                        typeDisplay = `PickMe - ${order.pickme_ref_number}`;
                                    } else {
                                        typeDisplay = order.order_type || 'N/A';
                                    }

                                    return `
                                                                                                                                                                                                                                                        <div class="bg-gray-700 rounded-lg p-4 hover:bg-gray-600 cursor-pointer transition"
                                                                                                                                                                                                                                                             onclick="loadOrder(${order.id})">
                                                                                                                                                                                                                                                            <div class="flex justify-between items-center">
                                                                                                                                                                                                                                                                <div>
                                                                                                                                                                                                                                                                    <div class="text-white font-semibold">${order.order_number}</div>
                                                                                                                                                                                                                                                                    <div class="text-sm text-gray-400">
                                                                                                                                                                                                                                                                        ${typeDisplay} | ${order.items_count} items
                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                <div class="text-right">
                                                                                                                                                                                                                                                                    <div class="text-white font-bold">Rs. ${parseFloat(order.total_amount).toFixed(2)}</div>
                                                                                                                                                                                                                                                                    <div class="text-xs text-gray-400">${order.created_at}</div>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                        `;
                                }).join('');
                            }

                            document.getElementById('openChecksModal').classList.remove('hidden');
                        } else {
                            // API returned success: false
                            showNotification(result.message || 'Failed to load open checks', 'Error');
                        }
                    } catch (error) {
                        console.error('Open checks error:', error);
                        showNotification('Error loading open checks: ' + error.message, 'Error');
                    }
                }

                // Load existing order
                async function loadOrder(orderId) {
                    try {
                        // Cancel any voided order before loading a new one (if loading a different order)
                        if (voidedOrderId && voidedOrderId !== orderId) {
                            await cancelVoidedOrderIfExists();
                        }

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

                            // Load items - merge duplicates if any exist in database (use modifier_id for matching)
                            const mergedItems = {};
                            result.order.items.forEach(item => {
                                // Use item_id + modifier_id for accurate matching (ID-based)
                                // Use explicit null/undefined check to preserve modifier_id values
                                const modId = (item.modifier_id !== null && item.modifier_id !== undefined) ? item.modifier_id : 'null';
                                const key = item.item_id + '_' + modId + '_' + item.name;
                                if (mergedItems[key]) {
                                    // Duplicate found - merge quantities
                                    mergedItems[key].quantity += parseInt(item.quantity);
                                    mergedItems[key].delivered_quantity += parseInt(item.delivered_quantity || 0);
                                    if ((mergedItems[key].status || 'preparing') !== 'delivered' || item.status !== 'delivered') {
                                        mergedItems[key].status = 'preparing';
                                        mergedItems[key].delivered_at = null;
                                        mergedItems[key].preparing_to_delivered_seconds = null;
                                    }

                                    if (!mergedItems[key].preparing_at && item.preparing_at) {
                                        mergedItems[key].preparing_at = item.preparing_at;
                                    }
                                } else {
                                    // New item - include modifier_id for ID-based stock matching
                                    mergedItems[key] = {
                                        id: item.id || null,
                                        item_id: item.item_id,
                                        modifier_id: (item.modifier_id !== null && item.modifier_id !== undefined) ? item.modifier_id : null,
                                        name: item.name,
                                        price: parseFloat(item.price),
                                        quantity: parseInt(item.quantity),
                                        status: item.status || 'preparing',
                                        delivered_quantity: parseInt(item.delivered_quantity || 0),
                                        delivery_selected_quantity: 0,
                                        showDeliveryControls: false,
                                        isSupervisorMode: false,
                                        supervisorActionType: null,
                                        preparing_at: item.preparing_at || null,
                                        delivered_at: item.delivered_at || null,
                                        preparing_to_delivered_seconds: item.preparing_to_delivered_seconds ?? null,
                                        modifiers: item.modifiers || []
                                    };
                                }
                            });

                            // Debug: log loaded items
                            console.log('Loaded order items:', Object.values(mergedItems).map(i => ({
                                item_id: i.item_id,
                                modifier_id: i.modifier_id,
                                name: i.name,
                                quantity: i.quantity
                            })));

                            // Convert to array
                            billItems = Object.values(mergedItems);

                            // Store original items for VOID functionality (deep copy)
                            originalOrderItems = JSON.parse(JSON.stringify(billItems));

                            // Enable VOID button since this is an existing order with items
                            const voidBtn = document.getElementById('voidButton');
                            if (voidBtn && originalOrderItems.length > 0) {
                                voidBtn.disabled = false;
                                voidBtn.classList.remove('text-gray-500', 'cursor-not-allowed', 'disabled:opacity-50');
                                voidBtn.classList.add('text-white', 'hover:bg-gray-600', 'cursor-pointer');
                            }

                            renderBill();
                            calculateTotals();

                            // Show menu section
                            document.getElementById('menuSelectionContainer').classList.remove('hidden');
                            document.getElementById('menuSelectionContainer').classList.add('flex');
                            const msg = document.getElementById('initialStateMessage');
                            if (msg) msg.classList.add('hidden');

                            showNotification('Order #' + result.order.order_number + ' loaded. You can add more items or close the order.', 'Order Loaded');

                            // Show/hide Transfer Table button based on order type
                            updateTransferTableButtonVisibility();
                        }
                    } catch (error) {
                        showNotification('Error loading order: ' + error.message, 'Error');
                    }
                }

                async function deliverBillItem(index) {
                    const item = billItems[index];
                    if (!item || !item.id) {
                        showNotification('Only saved order items can be delivered', 'Action Not Allowed');
                        return;
                    }

                    if (item.isSupervisorMode) {
                        await applySupervisorDeliveryOverride(index);
                        return;
                    }

                    const deliveryState = normalizeDeliverySelection(item);
                    if (deliveryState.remaining_quantity === 0 || item.status === 'delivered') {
                        return;
                    }

                    const selectedDeliveryQty = Math.min(
                        Math.max(parseInt(item.delivery_selected_quantity) || 0, 0),
                        deliveryState.remaining_quantity
                    );

                    if (selectedDeliveryQty <= 0) {
                        showNotification('Select at least 1 item to deliver', 'Delivery Quantity Required');
                        return;
                    }

                    try {
                        const response = await fetch(`{{ url('/pos/order-item/update-delivery') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                order_item_id: item.id,
                                delivered_amount: selectedDeliveryQty
                            })
                        });

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update item status');
                        }

                        billItems[index].status = result.order_item?.status || billItems[index].status;
                        if (result.order_item) {
                            billItems[index].delivered_quantity = parseInt(result.order_item.delivered_quantity || billItems[index].delivered_quantity || 0);
                            billItems[index].preparing_at = result.order_item.preparing_at || billItems[index].preparing_at || null;
                            billItems[index].delivered_at = result.order_item.delivered_at || null;
                            billItems[index].preparing_to_delivered_seconds = result.order_item.preparing_to_delivered_seconds ?? null;
                        }

                        const updatedState = normalizeDeliverySelection(billItems[index]);
                        billItems[index].isSupervisorMode = false;
                        billItems[index].supervisorActionType = null;
                        billItems[index].showDeliveryControls = updatedState.remaining_quantity > 0;
                        billItems[index].delivery_selected_quantity = updatedState.remaining_quantity;

                        renderBill();
                        showNotification(`${selectedDeliveryQty} item${selectedDeliveryQty > 1 ? 's' : ''} delivered successfully`, 'Success');
                    } catch (error) {
                        showNotification(error.message || 'Failed to deliver item', 'Error');
                    }
                }

                async function applySupervisorDeliveryOverride(index) {
                    const item = billItems[index];
                    if (!item || !item.id) {
                        return;
                    }

                    if (!verifiedSupervisorPin) {
                        showNotification('Supervisor authorization required', 'Authorization Required');
                        return;
                    }

                    const deliveryState = getDeliveryState(item);
                    const correctedDeliveredQuantity = Math.min(
                        Math.max(parseInt(item.delivery_selected_quantity) || 0, 0),
                        deliveryState.total_quantity
                    );

                    try {
                        const response = await fetch(`{{ url('/pos/order-item/supervisor-override') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                order_item_id: item.id,
                                supervisor_pin: verifiedSupervisorPin,
                                delivered_quantity: correctedDeliveredQuantity,
                                action_type: item.supervisorActionType || 'supervisor_override'
                            })
                        });

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to apply supervisor override');
                        }

                        billItems[index].status = result.order_item?.status || billItems[index].status;
                        if (result.order_item) {
                            billItems[index].delivered_quantity = parseInt(result.order_item.delivered_quantity || billItems[index].delivered_quantity || 0);
                            billItems[index].preparing_at = result.order_item.preparing_at || billItems[index].preparing_at || null;
                            billItems[index].delivered_at = result.order_item.delivered_at || null;
                            billItems[index].preparing_to_delivered_seconds = result.order_item.preparing_to_delivered_seconds ?? null;
                        }

                        const updatedState = normalizeDeliverySelection(billItems[index]);
                        billItems[index].isSupervisorMode = false;
                        billItems[index].supervisorActionType = null;
                        billItems[index].showDeliveryControls = updatedState.remaining_quantity > 0;
                        billItems[index].delivery_selected_quantity = updatedState.remaining_quantity;

                        renderBill();
                        showNotification('Supervisor override applied', 'Success');
                    } catch (error) {
                        showNotification(error.message || 'Failed to apply supervisor override', 'Error');
                    }
                }

                function requestPrepareStatusChange(index) {
                    const item = billItems[index];
                    if (!item || !item.id || item.status !== 'delivered') {
                        return;
                    }

                    supervisorPinContext = 'status-revert';
                    pendingStatusRevertIndex = index;

                    document.getElementById('supervisorPinInput').value = '';
                    document.getElementById('supervisorPinError').classList.add('hidden');
                    verifiedSupervisorPin = null;

                    document.getElementById('supervisorPinModal').classList.remove('hidden');
                    setTimeout(() => {
                        document.getElementById('supervisorPinInput').focus();
                    }, 100);
                }

                async function revertBillItemToPreparing(index, pin) {
                    const item = billItems[index];
                    if (!item || !item.id) {
                        showNotification('Invalid item selected', 'Error');
                        return;
                    }

                    try {
                        const response = await fetch(`{{ url('/pos/order-items') }}/${item.id}/prepare`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                supervisor_pin: pin
                            })
                        });

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Failed to update item status');
                        }

                        billItems[index].status = 'preparing';
                        billItems[index].preparing_at = result.order_item?.preparing_at || null;
                        billItems[index].delivered_at = null;
                        billItems[index].preparing_to_delivered_seconds = null;
                        renderBill();
                        showNotification(result.message || 'Item moved to preparing', 'Success');
                    } catch (error) {
                        showNotification(error.message || 'Failed to update item status', 'Error');
                    }
                }

                // Update Transfer Table button visibility
                function updateTransferTableButtonVisibility() {
                    const transferBtn = document.getElementById('transferTableBtn');
                    if (transferBtn) {
                        // Enable Transfer Table button only for dine-in orders with a table assigned
                        if (currentOrderType === 'dine_in' && selectedTableId && currentOrderId) {
                            transferBtn.disabled = false;
                            transferBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            transferBtn.classList.add('cursor-pointer');
                        } else {
                            transferBtn.disabled = true;
                            transferBtn.classList.add('opacity-50', 'cursor-not-allowed');
                            transferBtn.classList.remove('cursor-pointer');
                        }
                    }
                }

                // Open Table Transfer Modal
                async function openTableTransferModal() {
                    if (!currentOrderId) {
                        showNotification('No active order to transfer', 'No Order');
                        return;
                    }

                    if (currentOrderType !== 'dine_in') {
                        showNotification('Only dine-in orders can be transferred', 'Invalid Order Type');
                        return;
                    }

                    if (!selectedTableId) {
                        showNotification('Current order has no table assigned', 'No Table');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("pos.tables") }}');
                        const result = await response.json();

                        if (result.success) {
                            const tableGrid = document.getElementById('tableTransferGrid');
                            tableGrid.innerHTML = result.tables.map(table => {
                                // Determine if this is the current table
                                const isCurrentTable = table.id === selectedTableId;

                                let bgColor = 'bg-green-600 hover:bg-green-700';
                                let clickable = true;
                                let label = '';

                                if (isCurrentTable) {
                                    bgColor = 'bg-blue-600 cursor-not-allowed opacity-80';
                                    clickable = false;
                                    label = '<br><span class="text-xs">(Current Table)</span>';
                                } else if (!table.is_available) {
                                    bgColor = 'bg-red-600 cursor-not-allowed opacity-60';
                                    clickable = false;
                                    label = '<br><span class="text-xs">(Reserved)</span>';
                                }

                                return `
                                                                                                                                                                                                                                                        <button
                                                                                                                                                                                                                                                            ${clickable ? `onclick="confirmTableTransfer('${table.table_number}', ${table.id})"` : 'disabled'}
                                                                                                                                                                                                                                                            class="p-4 ${bgColor} text-white rounded-lg transition font-semibold">
                                                                                                                                                                                                                                                            ${table.table_number}
                                                                                                                                                                                                                                                            ${label}
                                                                                                                                                                                                                                                        </button>
                                                                                                                                                                                                                                                    `;
                            }).join('');

                            document.getElementById('tableTransferModal').classList.remove('hidden');
                        }
                    } catch (error) {
                        showNotification('Error loading tables: ' + error.message, 'Error');
                    }
                }

                // Confirm table transfer
                async function confirmTableTransfer(newTableNumber, newTableId) {
                    if (!currentOrderId) {
                        showNotification('No active order to transfer', 'No Order');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("pos.transferTable") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                order_id: currentOrderId,
                                new_table_id: newTableId
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            // Update local state
                            selectedTableId = newTableId;

                            // Update display
                            const display = document.getElementById('orderTypeDisplay');
                            if (display) {
                                display.textContent = 'Table: ' + newTableNumber;
                            }

                            closeModal('tableTransferModal');
                            showNotification(`Order successfully transferred to ${newTableNumber}`, 'Transfer Complete');
                        } else {
                            showNotification(result.message || 'Failed to transfer table', 'Transfer Failed');
                        }
                    } catch (error) {
                        showNotification('Error transferring table: ' + error.message, 'Error');
                    }
                }



                // Calculate change for close order
                document.getElementById('closeOrderAmountPaid')?.addEventListener('input', function () {
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

                    const hasUndeliveredItems = billItems.some(item => {
                        const status = (item?.status || '').toString().toLowerCase();
                        return status !== 'delivered';
                    });

                    if (hasUndeliveredItems) {
                        showNotification('Cannot close order until all items are Delivered', 'Action Not Allowed');
                        return;
                    }

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
                    openMergeOrderModal();
                }

                // Open Merge Order Modal
                async function openMergeOrderModal() {
                    // Check if there's an active order
                    if (!currentOrderId) {
                        showNotification('Please open an order first before merging', 'No Active Order');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("pos.openChecks") }}');
                        const result = await response.json();

                        if (result.success) {
                            const mergeGrid = document.getElementById('mergeOrderGrid');

                            // Filter out the current order from the list
                            const availableOrders = result.orders.filter(order => order.id !== currentOrderId);

                            if (availableOrders.length === 0) {
                                mergeGrid.innerHTML = `
                                                                                                                                                                                                                                                        <div class="col-span-3 text-center text-gray-500 py-8">
                                                                                                                                                                                                                                                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                                                                                                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                                                                                                                                                                                                            </svg>
                                                                                                                                                                                                                                                            <p class="text-lg font-semibold">No other orders available to merge</p>
                                                                                                                                                                                                                                                            <p class="text-sm text-gray-400 mt-1">All open orders are currently unavailable for merging</p>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    `;
                            } else {
                                mergeGrid.innerHTML = availableOrders.map(order => {
                                    let typeDisplay = '';
                                    let typeBadgeColor = 'bg-gray-600';

                                    if (order.order_type === 'dine_in' && order.table_number !== 'N/A') {
                                        typeDisplay = `Table: ${order.table_number}`;
                                        typeBadgeColor = 'bg-emerald-600';
                                    } else if (order.order_type === 'takeaway') {
                                        typeDisplay = 'Take Away';
                                        typeBadgeColor = 'bg-amber-500';
                                    } else if (order.order_type === 'pickme' && order.pickme_ref_number) {
                                        typeDisplay = `PickMe: ${order.pickme_ref_number}`;
                                        typeBadgeColor = 'bg-pink-500';
                                    } else {
                                        typeDisplay = order.order_type || 'N/A';
                                    }

                                    return `
                                                                                                                                                                                                                                                            <button
                                                                                                                                                                                                                                                                onclick="selectOrderToMerge(${order.id})"
                                                                                                                                                                                                                                                                class="p-4 bg-gray-700 hover:bg-teal-600 text-white rounded-lg transition border-2 border-gray-600 hover:border-teal-500 text-left">
                                                                                                                                                                                                                                                                <div class="flex justify-between items-start mb-2">
                                                                                                                                                                                                                                                                    <div class="font-bold text-lg">${order.order_number}</div>
                                                                                                                                                                                                                                                                    <span class="text-xs px-2 py-1 rounded ${typeBadgeColor}">${typeDisplay}</span>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                <div class="text-sm text-gray-300 mb-2">
                                                                                                                                                                                                                                                                    <span class="inline-flex items-center">
                                                                                                                                                                                                                                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                                                                                                                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                                                                                                                                                                                                                                        </svg>
                                                                                                                                                                                                                                                                        ${order.items_count} items
                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                                <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-600">
                                                                                                                                                                                                                                                                    <span class="text-xs text-gray-400">${order.created_at}</span>
                                                                                                                                                                                                                                                                    <span class="font-bold text-teal-400">Rs. ${parseFloat(order.total_amount).toFixed(2)}</span>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                            </button>
                                                                                                                                                                                                                                                        `;
                                }).join('');
                            }

                            document.getElementById('mergeOrderModal').classList.remove('hidden');
                        }
                    } catch (error) {
                        showNotification('Error loading orders: ' + error.message, 'Error');
                    }
                }

                // Select order to merge
                async function selectOrderToMerge(sourceOrderId) {
                    if (!currentOrderId) {
                        showNotification('No active order to merge into', 'Error');
                        return;
                    }

                    showConfirmation(
                        'Are you sure you want to merge the selected order into the current order? This will move all items from the selected order to the current order and cancel the selected order.',
                        'Confirm Merge',
                        async () => {
                            try {
                                const response = await fetch('{{ route("pos.mergeOrder") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        target_order_id: currentOrderId,
                                        source_order_id: sourceOrderId
                                    })
                                });

                                const result = await response.json();

                                if (result.success) {
                                    closeModal('mergeOrderModal');

                                    // Reload the current order to get updated items
                                    await loadOrder(currentOrderId);

                                    showNotification(result.message || 'Orders merged successfully', 'Merge Complete');
                                } else {
                                    showNotification(result.message || 'Failed to merge orders', 'Merge Failed');
                                }
                            } catch (error) {
                                showNotification('Error merging orders: ' + error.message, 'Error');
                            }
                        }
                    );
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
                document.getElementById('searchItems')?.addEventListener('input', function () {
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

                // Category bar scroll functions
                function scrollCategoriesLeft() {
                    const categoryTabs = document.getElementById('categoryTabs');
                    if (categoryTabs) {
                        categoryTabs.scrollBy({
                            left: -200,
                            behavior: 'smooth'
                        });
                    }
                }

                function scrollCategoriesRight() {
                    const categoryTabs = document.getElementById('categoryTabs');
                    if (categoryTabs) {
                        categoryTabs.scrollBy({
                            left: 200,
                            behavior: 'smooth'
                        });
                    }
                }

                function updateCategoryScrollButtons() {
                    const categoryTabs = document.getElementById('categoryTabs');
                    const leftBtn = document.getElementById('scrollLeftBtn');
                    const rightBtn = document.getElementById('scrollRightBtn');

                    if (!categoryTabs || !leftBtn || !rightBtn) return;

                    const isScrollable = categoryTabs.scrollWidth > categoryTabs.clientWidth;
                    const isAtStart = categoryTabs.scrollLeft <= 5;
                    const isAtEnd = categoryTabs.scrollLeft + categoryTabs.clientWidth >= categoryTabs.scrollWidth - 5;

                    if (isScrollable) {
                        // Show/hide left button
                        if (isAtStart) {
                            leftBtn.style.display = 'none';
                        } else {
                            leftBtn.style.display = 'block';
                            leftBtn.classList.remove('opacity-0', 'pointer-events-none');
                        }

                        // Show/hide right button
                        if (isAtEnd) {
                            rightBtn.style.display = 'none';
                        } else {
                            rightBtn.style.display = 'block';
                        }
                    } else {
                        leftBtn.style.display = 'none';
                        rightBtn.style.display = 'none';
                    }
                }

                // Initialize category scroll buttons
                document.addEventListener('DOMContentLoaded', () => {
                    const categoryTabs = document.getElementById('categoryTabs');
                    if (categoryTabs) {
                        // Update buttons on scroll
                        categoryTabs.addEventListener('scroll', updateCategoryScrollButtons);

                        // Initial update (delayed to ensure DOM is ready)
                        setTimeout(updateCategoryScrollButtons, 100);

                        // Update on window resize
                        window.addEventListener('resize', updateCategoryScrollButtons);
                    }
                });

                // Placeholder functions
                function showModifiersModal() {
                    showNotification('Modifiers feature coming soon', 'Feature Unavailable');
                }

                function voidItem() {
                    showNotification('Select an item to void', 'Void Item');
                }

                // ==================== VOID FUNCTIONALITY ====================

                // Open Supervisor PIN Modal for VOID authorization
                function openVoidPinModal() {
                    if (!currentOrderId) {
                        showNotification('No active order to void', 'Error');
                        return;
                    }

                    if (originalOrderItems.length === 0) {
                        showNotification('No items available to void. Only previously ordered items can be voided.', 'Error');
                        return;
                    }

                    // Reset PIN input and error
                    document.getElementById('supervisorPinInput').value = '';
                    document.getElementById('supervisorPinError').classList.add('hidden');
                    verifiedSupervisorPin = null;
                    supervisorPinContext = 'void';
                    pendingStatusRevertIndex = null;
                    pendingDeliveryOverrideIndex = null;
                    pendingDeliveryOverrideAction = null;

                    // Show PIN modal
                    document.getElementById('supervisorPinModal').classList.remove('hidden');

                    // Focus on PIN input
                    setTimeout(() => {
                        document.getElementById('supervisorPinInput').focus();
                    }, 100);
                }

                // Verify Supervisor PIN
                async function verifySupervisorPin() {
                    const pin = document.getElementById('supervisorPinInput').value.trim();

                    if (pin.length !== 4) {
                        document.getElementById('supervisorPinError').textContent = 'PIN must be 4 digits.';
                        document.getElementById('supervisorPinError').classList.remove('hidden');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("pos.verifySupervisorPin") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                pin: pin
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            verifiedSupervisorPin = pin;
                            closeModal('supervisorPinModal');

                            if (supervisorPinContext === 'status-revert' && pendingStatusRevertIndex !== null) {
                                const targetIndex = pendingStatusRevertIndex;
                                pendingStatusRevertIndex = null;
                                supervisorPinContext = 'void';
                                await revertBillItemToPreparing(targetIndex, pin);
                            } else if (supervisorPinContext === 'delivery-override' && pendingDeliveryOverrideIndex !== null) {
                                const targetIndex = pendingDeliveryOverrideIndex;
                                const overrideAction = pendingDeliveryOverrideAction || 'correction';
                                pendingDeliveryOverrideIndex = null;
                                pendingDeliveryOverrideAction = null;
                                supervisorPinContext = 'void';
                                enableSupervisorDeliveryMode(targetIndex, overrideAction);
                                showNotification('Supervisor authorization applied', 'Authorization Success');
                            } else {
                                openVoidItemsModal();
                                showNotification('PIN verified. Welcome, ' + result.supervisor_name, 'Authorization Success');
                            }
                        } else {
                            document.getElementById('supervisorPinError').textContent = result.message || 'Invalid PIN. Please try again.';
                            document.getElementById('supervisorPinError').classList.remove('hidden');
                            document.getElementById('supervisorPinInput').value = '';
                            document.getElementById('supervisorPinInput').focus();
                        }
                    } catch (error) {
                        document.getElementById('supervisorPinError').textContent = 'Error verifying PIN. Please try again.';
                        document.getElementById('supervisorPinError').classList.remove('hidden');
                    }
                }

                // Open VOID Items Modal
                function openVoidItemsModal() {
                    // Reset void items list
                    voidItemsList = [];

                    // Populate dropdown with original order items
                    populateVoidItemDropdown();

                    // Clear void items table
                    renderVoidItemsTable();

                    // Show modal
                    document.getElementById('voidItemsModal').classList.remove('hidden');
                }

                // Close VOID Items Modal
                function closeVoidItemsModal() {
                    document.getElementById('voidItemsModal').classList.add('hidden');
                    voidItemsList = [];
                    verifiedSupervisorPin = null;
                }

                // Populate VOID Item Dropdown
                function populateVoidItemDropdown() {
                    const dropdown = document.getElementById('voidItemDropdown');
                    dropdown.innerHTML = '<option value="">-- Select an item --</option>';

                    originalOrderItems.forEach((item, index) => {
                        // Check how much has already been added to void list (use modifier_id for accurate matching)
                        const alreadyVoided = voidItemsList
                            .filter(v => v.item_id === item.item_id && v.modifier_id === item.modifier_id && v.item_name === item.name)
                            .reduce((sum, v) => sum + v.void_quantity, 0);

                        const remainingQty = item.quantity - alreadyVoided;

                        if (remainingQty > 0) {
                            const option = document.createElement('option');
                            option.value = JSON.stringify({
                                item_id: item.item_id,
                                modifier_id: item.modifier_id || null, // Include modifier_id for ID-based matching
                                item_name: item.name,
                                price: item.price,
                                current_quantity: item.quantity,
                                remaining_quantity: remainingQty
                            });
                            option.textContent = `${item.name} (Qty: ${remainingQty})`;
                            dropdown.appendChild(option);
                        }
                    });

                    // Update max quantity hint
                    updateVoidQtyHint();
                }

                // Update void quantity hint when dropdown changes
                document.addEventListener('DOMContentLoaded', function () {
                    const dropdown = document.getElementById('voidItemDropdown');
                    if (dropdown) {
                        dropdown.addEventListener('change', updateVoidQtyHint);
                    }
                });

                function updateVoidQtyHint() {
                    const dropdown = document.getElementById('voidItemDropdown');
                    const hintEl = document.getElementById('voidItemMaxQty');
                    const qtyInput = document.getElementById('voidQuantityInput');

                    if (dropdown.value) {
                        const itemData = JSON.parse(dropdown.value);
                        hintEl.textContent = `Max quantity to void: ${itemData.remaining_quantity}`;
                        qtyInput.max = itemData.remaining_quantity;
                        qtyInput.value = 1;
                    } else {
                        hintEl.textContent = '';
                        qtyInput.max = '';
                    }
                }

                // Add item to void list
                function addVoidItem() {
                    const dropdown = document.getElementById('voidItemDropdown');
                    const qtyInput = document.getElementById('voidQuantityInput');

                    if (!dropdown.value) {
                        showNotification('Please select an item to void', 'Error');
                        return;
                    }

                    const itemData = JSON.parse(dropdown.value);
                    const voidQty = parseInt(qtyInput.value) || 1;

                    if (voidQty <= 0) {
                        showNotification('Void quantity must be at least 1', 'Error');
                        return;
                    }

                    if (voidQty > itemData.remaining_quantity) {
                        showNotification(`Maximum void quantity is ${itemData.remaining_quantity}`, 'Error');
                        return;
                    }

                    // Check if item already exists in void list (use modifier_id for accurate matching)
                    const existingIndex = voidItemsList.findIndex(
                        v => v.item_id === itemData.item_id && v.modifier_id === itemData.modifier_id && v.item_name === itemData.item_name
                    );

                    if (existingIndex >= 0) {
                        // Update existing
                        const newTotal = voidItemsList[existingIndex].void_quantity + voidQty;
                        if (newTotal > itemData.current_quantity) {
                            showNotification(`Total void quantity cannot exceed ${itemData.current_quantity}`, 'Error');
                            return;
                        }
                        voidItemsList[existingIndex].void_quantity = newTotal;
                    } else {
                        // Add new - include modifier_id for ID-based matching
                        voidItemsList.push({
                            item_id: itemData.item_id,
                            modifier_id: itemData.modifier_id || null, // Include modifier_id for ID-based matching
                            item_name: itemData.item_name,
                            price: itemData.price,
                            current_quantity: itemData.current_quantity,
                            void_quantity: voidQty
                        });
                    }

                    // Refresh UI
                    populateVoidItemDropdown();
                    renderVoidItemsTable();
                    qtyInput.value = 1;
                }

                // Remove item from void list
                function removeVoidItem(index) {
                    voidItemsList.splice(index, 1);
                    populateVoidItemDropdown();
                    renderVoidItemsTable();
                }

                // Render void items table
                function renderVoidItemsTable() {
                    const tbody = document.getElementById('voidItemsTableBody');

                    if (voidItemsList.length === 0) {
                        tbody.innerHTML = `
                                                                                                                                                                                                                                                <tr id="noVoidItemsRow">
                                                                                                                                                                                                                                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                                                                                                                                                                                                                                        No items added to void. Select items above.
                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                            `;
                        return;
                    }

                    tbody.innerHTML = voidItemsList.map((item, index) => `
                                                                                                                                                                                                                                            <tr class="border-t border-gray-600">
                                                                                                                                                                                                                                                <td class="px-4 py-3 text-white">${item.item_name}</td>
                                                                                                                                                                                                                                                <td class="px-4 py-3 text-center text-gray-300">${item.current_quantity}</td>
                                                                                                                                                                                                                                                <td class="px-4 py-3 text-center text-orange-400 font-bold">-${item.void_quantity}</td>
                                                                                                                                                                                                                                                <td class="px-4 py-3 text-center">
                                                                                                                                                                                                                                                    <button onclick="removeVoidItem(${index})" class="text-red-400 hover:text-red-300 transition">
                                                                                                                                                                                                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                                                                                                                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                                                                                                                                                                                                        </svg>
                                                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                        `).join('');
                }

                // Process void items - send to server and print cancel KOT
                async function processVoidItems() {
                    console.log('=== PROCESS VOID ITEMS ===');
                    console.log('voidItemsList:', voidItemsList);
                    console.log('currentOrderId:', currentOrderId);
                    console.log('verifiedSupervisorPin:', verifiedSupervisorPin ? '****' : 'null');

                    if (voidItemsList.length === 0) {
                        showNotification('Please add items to void', 'Error');
                        return;
                    }

                    if (!verifiedSupervisorPin) {
                        showNotification('Supervisor authorization required', 'Error');
                        closeVoidItemsModal();
                        openVoidPinModal();
                        return;
                    }

                    try {
                        console.log('Sending void request...');
                        const requestBody = {
                            order_id: currentOrderId,
                            void_items: voidItemsList,
                            supervisor_pin: verifiedSupervisorPin
                        };
                        console.log('Request body:', requestBody);

                        const response = await fetch('{{ route("pos.voidItems") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(requestBody)
                        });

                        console.log('Response status:', response.status);
                        const result = await response.json();
                        console.log('Response result:', result);

                        if (result.success) {
                            // Update billItems with the new quantities
                            billItems = result.updated_items.map(item => ({
                                item_id: item.item_id,
                                name: item.name,
                                price: parseFloat(item.price) || 0,
                                quantity: parseInt(item.quantity) || 0,
                                modifiers: []
                            }));

                            // Update original items as well
                            originalOrderItems = JSON.parse(JSON.stringify(billItems));

                            // Re-render bill
                            renderBill();
                            calculateTotals();

                            // Close modal
                            closeVoidItemsModal();

                            // Prepare order info for cancel KOT printing
                            const orderInfo = {
                                table_number: selectedTableId ? document.getElementById('orderTypeDisplay').textContent.replace('Table: ', '') : null,
                                pickme_ref: pickMeRefNumber,
                                order_type: currentOrderType
                            };

                            // Print Cancel KOT and Cancel BOT separately (with delay between)
                            // Similar to regular KOT/BOT printing in place order section
                            if (result.cancel_kot_items && result.cancel_kot_items.length > 0) {
                                console.log('Printing Cancel KOT for kitchen items:', result.cancel_kot_items);
                                await printCancelKOT(result.cancel_kot_number, result.cancel_kot_items, 'KITCHEN', orderInfo);
                            }

                            // Add delay between prints to prevent printer queue issues
                            if (result.cancel_kot_items && result.cancel_kot_items.length > 0 &&
                                result.cancel_bot_items && result.cancel_bot_items.length > 0) {
                                await new Promise(resolve => setTimeout(resolve, 1000)); // 1 second delay
                            }

                            if (result.cancel_bot_items && result.cancel_bot_items.length > 0) {
                                console.log('Printing Cancel BOT for bar items:', result.cancel_bot_items);
                                await printCancelKOT(result.cancel_bot_number, result.cancel_bot_items, 'BAR', orderInfo);
                            }

                            // Check if all items were voided (order is empty but not cancelled yet)
                            if (result.all_items_voided) {
                                // Track this order ID for cancellation when user starts a new order
                                voidedOrderId = result.order_id;

                                // Disable VOID button since no items left
                                const voidBtn = document.getElementById('voidButton');
                                if (voidBtn) {
                                    voidBtn.disabled = true;
                                    voidBtn.classList.remove('text-white', 'hover:bg-gray-600', 'cursor-pointer');
                                    voidBtn.classList.add('text-gray-500', 'cursor-not-allowed', 'disabled:opacity-50');
                                }

                                showNotification(
                                    `All items voided by ${result.supervisor_name}. You can add new items to continue this order, or start a new order.`,
                                    'All Items Voided'
                                );
                            } else {
                                // Some items still remain - clear voided order tracking
                                voidedOrderId = null;

                                showNotification(
                                    `${result.voided_items.length} item(s) voided successfully by ${result.supervisor_name}. New total: Rs. ${parseFloat(result.new_total).toFixed(2)}`,
                                    'Void Successful'
                                );

                                // Disable VOID button if no more original items
                                if (originalOrderItems.length === 0) {
                                    const voidBtn = document.getElementById('voidButton');
                                    if (voidBtn) {
                                        voidBtn.disabled = true;
                                        voidBtn.classList.remove('text-white', 'hover:bg-gray-600', 'cursor-pointer');
                                        voidBtn.classList.add('text-gray-500', 'cursor-not-allowed', 'disabled:opacity-50');
                                    }
                                }

                                // Scroll to bill section to show updated items
                                const billSection = document.getElementById('billItems');
                                if (billSection) {
                                    billSection.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                }
                            }
                        } else {
                            console.error('Void failed:', result.message);
                            showNotification(result.message || 'Error voiding items', 'Error');
                        }
                    } catch (error) {
                        console.error('Error processing void:', error);
                        showNotification('Error processing void: ' + error.message, 'Error');
                    }
                }

                /**
                 * Print Cancel KOT/BOT with proper PDF structure matching regular KOT/BOT
                 * @param {string} kotNumber - Cancel KOT number
                 * @param {Array} items - Items to cancel
                 * @param {string} station - 'KITCHEN' or 'BAR'
                 * @param {Object} orderInfo - Order information for printing
                 */
                async function printCancelKOT(kotNumber, items, station, orderInfo = {}) {
                    if (!items || items.length === 0) {
                        console.log('No items to print on Cancel KOT');
                        return;
                    }

                    try {
                        const {
                            jsPDF
                        } = window.jspdf;

                        // CRITICAL: Calculate TALL page height to fit all items with large fonts
                        // Base height: header (90mm) + order info (90mm) + footer (40mm) = 220mm (extra for CANCEL banner)
                        // Per item: 50mm (accounts for 16pt font name + possible word wrap + 18pt quantity + separator + spacing)
                        // Safety buffer: 100mm extra for cancel banner and authorization text
                        const baseHeight = 220;
                        const perItemHeight = 50; // Very generous - accounts for 2-3 line item names
                        const safetyBuffer = 100; // Large buffer for cancel banner and authorization
                        const calculatedHeight = baseHeight + (items.length * perItemHeight) + safetyBuffer;
                        const pageHeight = Math.max(350, calculatedHeight); // Minimum 350mm for cancel KOT

                        // Debug log: Verify ALL items are being processed
                        console.log(`[Cancel KOT] Printing ${items.length} items | Page Height: ${pageHeight}mm | No limit applied`);

                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'mm',
                            format: [80, pageHeight], // Dynamic height based on items
                            compress: false // Disable compression to prevent font scaling issues
                        });

                        let yPosition = 10;
                        const pageWidth = 80;
                        const leftMargin = 5;
                        const rightMargin = 5;

                        // Header - CANCEL banner
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(20);
                        pdf.setTextColor(255, 0, 0); // Red text
                        pdf.text('*** CANCEL ***', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        pdf.setTextColor(0, 0, 0); // Back to black
                        pdf.setFontSize(16);
                        const ticketType = station === 'KITCHEN' ? 'KITCHEN CANCEL' : 'BAR CANCEL';
                        pdf.text(ticketType, pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        // Check if this is a cancel for a sub-KOT/BOT (has sub-number)
                        // Format: CANCEL-KOT-YYYYMMDD-XXXX-N (where N is the sub-number)
                        const kotParts = kotNumber ? String(kotNumber).split('-') : [];
                        const hasSubNumber = kotParts.length > 4; // CANCEL-KOT-DATE-NUM-SUB
                        const subNumber = hasSubNumber ? kotParts[kotParts.length - 1] : null;

                        pdf.setFontSize(12);
                        const cancelLabel = station === 'KITCHEN'
                            ? (hasSubNumber ? `(CANCEL KOT - ADDITION #${subNumber})` : '(CANCEL KOT)')
                            : (hasSubNumber ? `(CANCEL BOT - ADDITION #${subNumber})` : '(CANCEL BOT)');
                        pdf.text(cancelLabel, pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        // Restaurant Info
                        pdf.setFontSize(11);
                        pdf.text('RAVON BAKERS', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        // Separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // Cancel Order Information
                        pdf.setFontSize(11);
                        pdf.setFont('courier', 'bold');

                        pdf.text('NO #:', leftMargin, yPosition);
                        pdf.text(String(kotNumber || 'N/A'), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 6;

                        // Order Type
                        pdf.text('TYPE:', leftMargin, yPosition);
                        let typeText = '';
                        if (currentOrderType === 'dine_in' && orderInfo.table_number) {
                            typeText = 'Table ' + String(orderInfo.table_number);
                        } else if (currentOrderType === 'takeaway') {
                            typeText = 'Take Away';
                        } else if (currentOrderType === 'pickme' && orderInfo.pickme_ref) {
                            typeText = 'PickMe - ' + String(orderInfo.pickme_ref);
                        } else if (orderInfo.table_number) {
                            typeText = 'Table ' + String(orderInfo.table_number);
                        } else {
                            typeText = currentOrderType || 'N/A';
                        }
                        pdf.text(typeText, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 6;

                        pdf.text('CASHIER:', leftMargin, yPosition);
                        pdf.text('{{ Auth::user()->name }}', pageWidth - rightMargin, yPosition, {
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
                        yPosition += 6;

                        // Items separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 6;

                        // CANCELLED ITEMS header
                        pdf.setFontSize(14);
                        pdf.setTextColor(255, 0, 0); // Red
                        pdf.text('CANCELLED ITEMS:', leftMargin, yPosition);
                        pdf.setTextColor(0, 0, 0); // Back to black
                        yPosition += 8;

                        // Print Cancelled Items - LARGE FONT for kitchen readability
                        items.forEach((item, index) => {
                            pdf.setFont('courier', 'bold');
                            pdf.setFontSize(16); // INCREASED from 12 for better readability

                            let itemName = item.name || item.item_name;

                            // Word wrap for long item names
                            const maxWidth = pageWidth - leftMargin - rightMargin;
                            const lines = pdf.splitTextToSize(itemName, maxWidth);

                            lines.forEach(line => {
                                pdf.text(line, leftMargin, yPosition);
                                yPosition += 7; // Increased line spacing
                            });

                            // Cancelled Quantity (with minus sign) - LARGE and BOLD
                            pdf.setFontSize(18); // INCREASED from 14 for visibility
                            pdf.setTextColor(255, 0, 0); // Red for cancelled qty
                            pdf.text(`CANCEL x ${item.quantity}`, leftMargin + 2, yPosition);
                            pdf.setTextColor(0, 0, 0); // Back to black
                            yPosition += 8; // Increased spacing

                            // Add spacing between items
                            if (index < items.length - 1) {
                                pdf.setLineDashPattern([0.5, 0.5], 0);
                                pdf.setLineWidth(0.3);
                                pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                                pdf.setLineDashPattern([], 0);
                                yPosition += 5; // Increased spacing between items
                            }
                        });

                        // Footer
                        yPosition += 4;
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        yPosition += 8;

                        pdf.setFontSize(12);
                        pdf.setTextColor(255, 0, 0); // Red
                        pdf.text('** ITEMS CANCELLED **', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        pdf.setTextColor(0, 0, 0);
                        yPosition += 6;

                        pdf.setFontSize(10);
                        pdf.text('Authorized by Supervisor', pageWidth / 2, yPosition, {
                            align: 'center'
                        });

                        // Generate Base64 and Print directly to thermal printer
                        const pdfBase64 = pdf.output('datauristring').split(',')[1];

                        // Use configured printer or default printer
                        const cancelPrinterName = "OutletPOS";
                        await printPDFwithQZ(pdfBase64, cancelPrinterName, `Cancel ${station === 'KITCHEN' ? 'KOT' : 'BOT'}`, false);
                        console.log(`Cancel ${station === 'KITCHEN' ? 'KOT' : 'BOT'} sent to thermal printer successfully`);

                    } catch (error) {
                        console.error('Cancel KOT Generation Error:', error);
                        // Still show notification even if printing fails
                        showNotification(`Cancel ${station} order generated (check printer)`, 'Print Info');
                    }
                }

                // ==================== END VOID FUNCTIONALITY ====================

                // Print Invoice for current order (without payment details)
                async function printCurrentInvoice() {
                    if (!currentOrderId) {
                        showNotification('No active order to print', 'Error');
                        return;
                    }

                    try {
                        // Fetch current order details
                        const response = await fetch(`/pos/order/${currentOrderId}`);
                        const result = await response.json();

                        if (result.success && result.order) {
                            // Use the receipt printing function without payment details
                            await printInvoiceWithoutPayment(result.order);
                            showNotification('Invoice sent to printer', 'Success');
                        } else {
                            showNotification('Failed to load order details', 'Error');
                        }
                    } catch (error) {
                        console.error('Error printing invoice:', error);
                        showNotification('Error printing invoice: ' + error.message, 'Error');
                    }
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
                document.getElementById('searchItems')?.addEventListener('input', function () {
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



                // Select Payment Type
                window.selectPaymentType = function (type) {
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

                        // Auto-fill card amount with total (editable)
                        const total = parseFloat(document.getElementById('paymentTotal').textContent) || 0;
                        cardInputValue = total.toString();
                        document.getElementById('paymentCardInput').value = total.toFixed(2);
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
                window.setActivePaymentInput = function (fieldType) {
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
                window.appendNumber = function (num) {
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

                window.backspaceNumber = function () {
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

                window.clearNumber = function () {
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
                window.handlePaymentInputChange = function (inputType) {
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

                window.completePayment = async function () {
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
                        amountPaid = cardAmount;
                        if (cardAmount <= 0) {
                            showNotification('Please enter a valid card amount', 'Payment Error');
                            return;
                        }
                        if (cardAmount < total) {
                            showNotification('Insufficient card amount. Total: ' + total.toFixed(2), 'Payment Error');
                            return;
                        }
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

                            // Store order type before clearing (for conditional receipt printing)
                            const orderTypeBeforeClear = currentOrderType;

                            // Clear order state immediately
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

                            // Auto-print receipt ONLY for Takeaway orders
                            if (orderTypeBeforeClear === 'takeaway' && result.order) {
                                console.log('Takeaway order detected - Auto-printing receipt...');
                                try {
                                    await printReceiptWithQZ(result.order);
                                    console.log('Takeaway receipt printed successfully');
                                } catch (printError) {
                                    console.error('Failed to auto-print takeaway receipt:', printError);
                                    // Fallback: offer manual print option
                                    showConfirmation('Auto-print failed. Open receipt in new window?', 'Print Receipt', () => {
                                        window.open('/pos/receipt/' + result.order.id, '_blank');
                                    });
                                }
                            }
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
                        const {
                            jsPDF
                        } = window.jspdf;

                        // CRITICAL: Calculate TALL page height to fit all items
                        // Get items count for height calculation
                        const orderItemsForHeight = order.order_items || order.orderItems || [];
                        // Base height: header (100mm) + order info (70mm) + payment info (80mm) + footer (60mm) = 280mm
                        // Per item: 40mm (name with word wrap + modifiers + price lines + spacing)
                        // Additional height for modifiers: 8mm per modifier
                        let totalModifiersHeight = 0;
                        orderItemsForHeight.forEach(item => {
                            const modifiers = item.modifiers || [];
                            totalModifiersHeight += modifiers.length * 8;
                        });
                        const baseHeight = 280;
                        const perItemHeight = 40; // Very generous for receipts
                        const safetyBuffer = 100; // Large buffer to ensure content never gets cut off
                        const calculatedHeight = baseHeight + (orderItemsForHeight.length * perItemHeight) + totalModifiersHeight + safetyBuffer;
                        const pageHeight = Math.max(350, calculatedHeight); // Minimum 350mm for receipts

                        // Debug log: Verify ALL items are being processed
                        console.log(`[Receipt] Printing ${orderItemsForHeight.length} items | Page Height: ${pageHeight}mm | No limit applied`);

                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'mm',
                            format: [80, pageHeight], // Dynamic height based on items
                            compress: false // Disable compression to prevent font scaling issues
                        });

                        let yPosition = 8;
                        const pageWidth = 80;
                        const leftMargin = 3;
                        const rightMargin = 8;
                        const contentWidth = pageWidth - leftMargin - rightMargin;

                        // Header - Restaurant Name
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(16);
                        pdf.text('RAVON BAKERS', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 5;

                        pdf.setFontSize(10);
                        pdf.setFont('courier', 'normal');
                        pdf.text('Jayawardena Holdings (Pvt) Ltd', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;

                        pdf.setFontSize(9);
                        pdf.text('No 282/A/2, Kothalawala, Kaduwela', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;
                        pdf.text('Tel: +94 74 200 6007', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;

                        const vatRegNoStr = '{{ $vatRegNo ?? '' }}'.trim();
                        if (vatRegNoStr) {
                            pdf.text('VAT Reg No: ' + vatRegNoStr, pageWidth / 2, yPosition, {
                                align: 'center'
                            });
                            yPosition += 4;
                        }
                        yPosition += 4;

                        // Invoice Title
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(14);
                        pdf.text('INVOICE', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        // Order Information
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);

                        const customerName = order.customer_name || 'Cash Customer';
                        const customerVatNo = (order.customer_vat_number || '').toString().trim();

                        pdf.text('Customer :', leftMargin, yPosition);
                        pdf.text(customerName, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('VAT No :', leftMargin, yPosition);
                        pdf.text(customerVatNo ? customerVatNo : 'Not Eligible', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('Invoice #', leftMargin, yPosition);
                        pdf.text(String(order.order_number || order.id), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        const now = new Date();
                        const dateStr = now.toLocaleDateString('en-GB');
                        const timeStr = now.toLocaleTimeString('en-GB', {
                            hour12: false
                        });

                        pdf.text('Date', leftMargin, yPosition);
                        pdf.text(`:${dateStr} Time ${timeStr}`, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('Terminal:', leftMargin, yPosition);
                        pdf.text('01', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        // Determine table/order type display
                        let tableDisplay = '';
                        if (order.table && order.table.table_number) {
                            tableDisplay = String(order.table.table_number);
                        } else {
                            // Show order type for non-table orders
                            const orderType = order.order_type || 'takeaway';
                            if (orderType === 'pickme' && order.pickme_ref_number) {
                                tableDisplay = 'PickMe - ' + String(order.pickme_ref_number);
                            } else if (orderType === 'pickme') {
                                tableDisplay = 'PickMe Food';
                            } else if (orderType === 'uber_eats') {
                                tableDisplay = 'Uber Eats';
                            } else if (orderType === 'delivery') {
                                tableDisplay = 'Delivery';
                            } else if (orderType === 'takeaway') {
                                tableDisplay = 'Take Away';
                            } else {
                                tableDisplay = 'Take Away';
                            }
                        }

                        pdf.text('Table # :', leftMargin, yPosition);
                        pdf.text(tableDisplay, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        const cashier = order.waiter ? order.waiter.name : 'Cashier User';
                        pdf.text('Cashier :', leftMargin, yPosition);
                        pdf.text(String(cashier), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
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
                        pdf.text('Qty   Amount', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        // Print Items
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);

                        const vatRate_r = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                        const ssclRate_r = parseFloat('{{ $ssclRate ?? 0 }}') || 0;

                        const items = order.order_items || order.orderItems || [];
                        items.forEach((item, index) => {
                            const itemName = item.item_display_name || item.name || item.item?.name || item.item_name || 'Unknown Item';
                            const quantity = item.quantity || 0;
                            const inclSubtotal_r = parseFloat(item.subtotal || (parseFloat(item.unit_price || item.price || 0) * quantity));
                            const appliesVat_r = item.vat_available ? vatRate_r : 0;
                            const appliesSscl_r = item.sscl_available ? ssclRate_r : 0;
                            const taxFactor_item_r = (1 + appliesSscl_r / 100) * (1 + appliesVat_r / 100) || 1;
                            const baseSubtotal_r_item = inclSubtotal_r / taxFactor_item_r;
                            const unitPrice = (quantity > 0 ? baseSubtotal_r_item / quantity : 0).toFixed(2);
                            const subtotal = baseSubtotal_r_item.toFixed(2);
                            const modifiers = item.modifiers || [];

                            // Item number and name with portion (first line)
                            pdf.setFont('courier', 'bold');
                            pdf.setFontSize(10);
                            let displayName = `${index + 1}. ${itemName}`;
                            if (displayName.length > 28) {
                                displayName = displayName.substring(0, 25) + '...';
                            }
                            pdf.text(displayName, leftMargin, yPosition);
                            yPosition += 5;

                            // Second line: Quantity x Unit Price = Amount
                            pdf.setFont('courier', 'normal');
                            pdf.setFontSize(9);

                            // Quantity x @ Unit Price on the left
                            pdf.text(`${quantity}x @ Rs. ${unitPrice}`, leftMargin + 3, yPosition);

                            // Amount on the right side
                            pdf.text(subtotal, pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 5;

                            // Print modifiers (portion sizes, extras)
                            if (modifiers.length > 0) {
                                pdf.setFontSize(8);
                                modifiers.forEach(modifier => {
                                    const modName = modifier.name || 'Modifier';
                                    const modPrice = parseFloat(modifier.price_adjustment || 0).toFixed(2);
                                    const modText = `  + ${modName} (+Rs. ${modPrice})`;
                                    pdf.text(modText, leftMargin + 5, yPosition);
                                    yPosition += 4;
                                });
                                yPosition += 1; // Extra space after modifiers
                            }

                            yPosition += 1; // Space before next item
                        });

                        // Separator
                        yPosition += 2;
                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        // Subtotal
                        let baseTotal_r = 0;
                        let ssclAmount_r = 0;
                        let vatAmount_r = 0;

                        const items_r = order.order_items || order.orderItems || [];
                        items_r.forEach(item => {
                            const appliesVat = item.vat_available ? vatRate_r : 0;
                            const appliesSscl = item.sscl_available ? ssclRate_r : 0;
                            const itemTotal = parseFloat(item.subtotal || (item.price * item.quantity));

                            const itemBase = itemTotal / ((1 + (appliesSscl / 100)) * (1 + (appliesVat / 100)));
                            const itemSscl = itemBase * (appliesSscl / 100);
                            const itemVat = (itemBase + itemSscl) * (appliesVat / 100);

                            baseTotal_r += itemBase;
                            ssclAmount_r += itemSscl;
                            vatAmount_r += itemVat;
                        });

                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(10);
                        pdf.text('Sub Total (Base)', leftMargin, yPosition);
                        pdf.text(baseTotal_r.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 5;

                        pdf.text(`SSCL (${ssclRate_r}%)`, leftMargin, yPosition);
                        pdf.text(ssclAmount_r.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text(`VAT (${vatRate_r}%)`, leftMargin, yPosition);
                        pdf.text(vatAmount_r.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
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
                        pdf.text(parseFloat(order.total_amount || 0).toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 7;

                        // Payment Information
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);

                        const paymentMethod = order.payment?.payment_method?.toUpperCase() || 'CASH';
                        pdf.text('Payment Method', leftMargin, yPosition);
                        pdf.text(paymentMethod, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        const cashAmount = order.payment?.cash_amount || 0;
                        const cardAmount = order.payment?.card_amount || 0;
                        const creditAmount = order.payment?.credit_amount || 0;
                        const changeAmount = order.payment?.change_amount || 0;

                        if (cashAmount > 0) {
                            pdf.text('Cash', leftMargin, yPosition);
                            pdf.text(parseFloat(cashAmount).toFixed(2), pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 4;
                        }

                        if (cardAmount > 0) {
                            pdf.text('Card', leftMargin, yPosition);
                            pdf.text(parseFloat(cardAmount).toFixed(2), pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 4;
                        }

                        if (creditAmount > 0) {
                            pdf.text('Credit', leftMargin, yPosition);
                            pdf.text(parseFloat(creditAmount).toFixed(2), pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 4;
                        }

                        if (changeAmount > 0) {
                            pdf.text('Change', leftMargin, yPosition);
                            pdf.text(parseFloat(changeAmount).toFixed(2), pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 4;
                        }

                        yPosition += 3;

                        // Footer
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(10);
                        pdf.text('THANK YOU, COME AGAIN.', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(8);
                        pdf.text('Software By Jayawardena Group', pageWidth / 2, yPosition, {
                            align: 'center'
                        });

                        // Generate Base64 and Print directly to thermal printer
                        const pdfBase64 = pdf.output('datauristring').split(',')[1];

                        // Use configured printer or default printer
                        const receiptPrinterName = "OutletPOS";
                        await printPDFwithQZ(pdfBase64, receiptPrinterName, "Receipt", false);
                        console.log('Receipt sent to thermal printer successfully');

                    } catch (error) {
                        console.error('Receipt Generation Error:', error);
                        throw error;
                    }
                }

                /**
                 * Print Invoice WITHOUT Payment Details (For current orders before payment)
                 * @param {Object} order - Order object
                 */
                async function printInvoiceWithoutPayment(order) {
                    if (!order) {
                        console.error('No order data provided for invoice');
                        return;
                    }

                    try {
                        const {
                            jsPDF
                        } = window.jspdf;

                        // CRITICAL: Calculate TALL page height to fit all items
                        // Get items count for height calculation
                        const invoiceItemsForHeight = order.order_items || order.orderItems || billItems || [];
                        // Base height: header (90mm) + order info (70mm) + footer (50mm) = 250mm
                        // Per item: 40mm (name with word wrap + modifiers + price lines + spacing)
                        // Additional height for modifiers: 8mm per modifier
                        let totalModifiersHeightInv = 0;
                        invoiceItemsForHeight.forEach(item => {
                            const modifiers = item.modifiers || [];
                            totalModifiersHeightInv += modifiers.length * 8;
                        });
                        const baseHeight = 250;
                        const perItemHeight = 40; // Very generous for invoices
                        const safetyBuffer = 100; // Large buffer to ensure content never gets cut off
                        const calculatedHeight = baseHeight + (invoiceItemsForHeight.length * perItemHeight) + totalModifiersHeightInv + safetyBuffer;
                        const pageHeight = Math.max(350, calculatedHeight); // Minimum 350mm for invoices

                        // Debug log: Verify ALL items are being processed
                        console.log(`[Invoice] Printing ${invoiceItemsForHeight.length} items | Page Height: ${pageHeight}mm | No limit applied`);

                        const pdf = new jsPDF({
                            orientation: 'portrait',
                            unit: 'mm',
                            format: [80, pageHeight], // Dynamic height based on items
                            compress: false // Disable compression to prevent font scaling issues
                        });

                        let yPosition = 8;
                        const pageWidth = 80;
                        const leftMargin = 3;
                        const rightMargin = 8;

                        // Header - Restaurant Name
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(16);
                        pdf.text('RAVON BAKERS', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 5;

                        pdf.setFontSize(10);
                        pdf.setFont('courier', 'normal');
                        pdf.text('Jayawardena Holdings (Pvt) Ltd', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;

                        pdf.setFontSize(9);
                        pdf.text('No 282/A/2, Kothalawala, Kaduwela', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;
                        pdf.text('Tel: +94 74 200 6007', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 4;

                        const invVatRegNoStr = '{{ $vatRegNo ?? '' }}'.trim();
                        if (invVatRegNoStr) {
                            pdf.text('VAT Reg No: ' + invVatRegNoStr, pageWidth / 2, yPosition, {
                                align: 'center'
                            });
                            yPosition += 4;
                        }
                        yPosition += 4;

                        // Invoice Title
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(14);
                        pdf.text('INVOICE', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 8;

                        // Order Information
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);

                        const customerName = order.customer_name || 'Cash Customer';
                        const customerVatNo = (order.customer_vat_number || '').toString().trim();

                        pdf.text('Customer :', leftMargin, yPosition);
                        pdf.text(customerName, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('VAT No :', leftMargin, yPosition);
                        pdf.text(customerVatNo ? customerVatNo : 'Not Eligible', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('Invoice #', leftMargin, yPosition);
                        pdf.text(String(order.order_number || order.id), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        const now = new Date();
                        const dateStr = now.toLocaleDateString('en-GB');
                        const timeStr = now.toLocaleTimeString('en-GB', {
                            hour12: false
                        });

                        pdf.text('Date', leftMargin, yPosition);
                        pdf.text(`:${dateStr} Time ${timeStr}`, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text('Terminal:', leftMargin, yPosition);
                        pdf.text('01', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        // Determine table/order type display
                        let tableDisplay = '';
                        if (order.table && order.table.table_number) {
                            tableDisplay = String(order.table.table_number);
                        } else {
                            const orderType = order.order_type || 'takeaway';
                            if (orderType === 'pickme' && order.pickme_ref_number) {
                                tableDisplay = 'PickMe - ' + String(order.pickme_ref_number);
                            } else if (orderType === 'pickme') {
                                tableDisplay = 'PickMe Food';
                            } else if (orderType === 'takeaway') {
                                tableDisplay = 'Take Away';
                            } else {
                                tableDisplay = 'Take Away';
                            }
                        }

                        pdf.text('Table # :', leftMargin, yPosition);
                        pdf.text(tableDisplay, pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        const cashier = order.waiter ? order.waiter.name : '{{ Auth::user()->name }}';
                        pdf.text('Cashier :', leftMargin, yPosition);
                        pdf.text(String(cashier), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
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
                        pdf.text('Qty   Amount', pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        // Print Items
                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(9);

                        const vatRate_i = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                        const ssclRate_i = parseFloat('{{ $ssclRate ?? 0 }}') || 0;

                        const items = order.order_items || order.orderItems || [];
                        items.forEach((item, index) => {
                            const itemName = item.item_display_name || item.name || item.item?.name || item.item_name || 'Unknown Item';
                            const quantity = item.quantity || 0;
                            const inclSubtotal_i = parseFloat(item.subtotal || (parseFloat(item.unit_price || item.price || 0) * quantity));
                            const appliesVat_i = item.vat_available ? vatRate_i : 0;
                            const appliesSscl_i = item.sscl_available ? ssclRate_i : 0;
                            const taxFactor_item_i = (1 + appliesSscl_i / 100) * (1 + appliesVat_i / 100) || 1;
                            const baseSubtotal_i_item = inclSubtotal_i / taxFactor_item_i;
                            const unitPrice = (quantity > 0 ? baseSubtotal_i_item / quantity : 0).toFixed(2);
                            const subtotal = baseSubtotal_i_item.toFixed(2);
                            const modifiers = item.modifiers || [];

                            // Item number and name with portion (first line)
                            pdf.setFont('courier', 'bold');
                            pdf.setFontSize(10);
                            let displayName = `${index + 1}. ${itemName}`;
                            if (displayName.length > 28) {
                                displayName = displayName.substring(0, 25) + '...';
                            }
                            pdf.text(displayName, leftMargin, yPosition);
                            yPosition += 5;

                            // Second line: Quantity x @ Unit Price = Amount
                            pdf.setFont('courier', 'normal');
                            pdf.setFontSize(9);

                            pdf.text(`${quantity}x @ Rs. ${unitPrice}`, leftMargin + 3, yPosition);
                            pdf.text(subtotal, pageWidth - rightMargin, yPosition, {
                                align: 'right'
                            });
                            yPosition += 5;

                            // Print modifiers (portion sizes, extras)
                            if (modifiers.length > 0) {
                                pdf.setFontSize(8);
                                modifiers.forEach(modifier => {
                                    const modName = modifier.name || 'Modifier';
                                    const modPrice = parseFloat(modifier.price_adjustment || 0).toFixed(2);
                                    pdf.text(`  + ${modName} (+Rs. ${modPrice})`, leftMargin + 5, yPosition);
                                    yPosition += 4;
                                });
                                yPosition += 1;
                            }

                            yPosition += 1;
                        });

                        // Separator
                        yPosition += 2;
                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        // Subtotal
                        let baseTotal_i = 0;
                        let ssclAmount_i = 0;
                        let vatAmount_i = 0;

                        const items_i = order.order_items || order.orderItems || [];
                        items_i.forEach(item => {
                            const appliesVat = item.vat_available ? vatRate_i : 0;
                            const appliesSscl = item.sscl_available ? ssclRate_i : 0;
                            const itemTotal = parseFloat(item.subtotal || (item.price * item.quantity));

                            const itemBase = itemTotal / ((1 + (appliesSscl / 100)) * (1 + (appliesVat / 100)));
                            const itemSscl = itemBase * (appliesSscl / 100);
                            const itemVat = (itemBase + itemSscl) * (appliesVat / 100);

                            baseTotal_i += itemBase;
                            ssclAmount_i += itemSscl;
                            vatAmount_i += itemVat;
                        });

                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(10);
                        pdf.text('Sub Total (Base)', leftMargin, yPosition);
                        pdf.text(baseTotal_i.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 5;

                        pdf.text(`SSCL (${ssclRate_i}%)`, leftMargin, yPosition);
                        pdf.text(ssclAmount_i.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 4;

                        pdf.text(`VAT (${vatRate_i}%)`, leftMargin, yPosition);
                        pdf.text(vatAmount_i.toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 5;

                        // Total Separator
                        pdf.setLineWidth(0.5);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineWidth(0.2);
                        yPosition += 5;

                        // Grand Total
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(11);
                        pdf.text('Total', leftMargin, yPosition);
                        pdf.text(parseFloat(order.total_amount || 0).toFixed(2), pageWidth - rightMargin, yPosition, {
                            align: 'right'
                        });
                        yPosition += 7;

                        // NO PAYMENT DETAILS - That's the key difference from receipt

                        // Footer
                        pdf.setFont('courier', 'bold');
                        pdf.setFontSize(10);
                        pdf.text('THANK YOU, COME AGAIN.', pageWidth / 2, yPosition, {
                            align: 'center'
                        });
                        yPosition += 6;

                        pdf.setLineDashPattern([1, 1], 0);
                        pdf.line(leftMargin, yPosition, pageWidth - rightMargin, yPosition);
                        pdf.setLineDashPattern([], 0);
                        yPosition += 4;

                        pdf.setFont('courier', 'normal');
                        pdf.setFontSize(8);
                        pdf.text('Software By SKM Labs', pageWidth / 2, yPosition, {
                            align: 'center'
                        });

                        // Generate Base64 and Print directly to thermal printer
                        const pdfBase64 = pdf.output('datauristring').split(',')[1];

                        // Use configured printer or default printer
                        const invoicePrinterName = "OutletPOS";
                        await printPDFwithQZ(pdfBase64, invoicePrinterName, "Invoice", false);
                        console.log('Invoice (without payment) sent to thermal printer successfully');

                    } catch (error) {
                        console.error('Invoice Generation Error:', error);
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
                        const vatRate_html = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                        const ssclRate_html = parseFloat('{{ $ssclRate ?? 0 }}') || 0;
                        items.forEach(item => {
                            itemCount++;
                            const itemName = item.item?.name || item.item_name || 'Unknown Item';
                            const itemCode = item.item?.item_code || item.item_code || '';
                            const quantity = item.quantity || 0;
                            const inclSubtotal_html = parseFloat(item.subtotal || (parseFloat(item.unit_price || 0) * quantity));
                            const appliesVat_html = item.vat_available ? vatRate_html : 0;
                            const appliesSscl_html = item.sscl_available ? ssclRate_html : 0;
                            const taxFactor_html = (1 + appliesSscl_html / 100) * (1 + appliesVat_html / 100) || 1;
                            const baseSubtotal_html = inclSubtotal_html / taxFactor_html;
                            const unitPrice = (quantity > 0 ? baseSubtotal_html / quantity : 0).toFixed(2);
                            const subtotal = baseSubtotal_html.toFixed(2);

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
                                                                                                                                                                                                                                    padding: 2mm 6mm 2mm 2mm;
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
                                                                                                                                                                                                                                <h1>RAVON BAKERS</h1>
                                                                                                                                                                                                                                <div class="subtitle">Jayawardena Holdings (Pvt) Ltd</div>
                                                                                                                                                                                                                                <div class="address">
                                                                                                                                                                                                                                    No 282/A/2, Kothalawala, Kaduwela<br>
                                                                                                                                                                                                                                    Tel: +94 74 200 6007<br>
                                                                                                                                                                                                                                    @if(!empty($vatRegNo))
                                                                                                                                                                                                                                        VAT Reg No: {{ $vatRegNo }}
                                                                                                                                                                                                                                    @endif
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                            <div class="invoice-title">INVOICE</div>

                                                                                                                                                                                                                            <div class="section">
                                                                                                                                                                                                                                <div class="info-row">
                                                                                                                                                                                                                                    <span class="label">Customer :</span>
                                                                                                                                                                                                                                    <span>${order.customer_name || 'Cash Customer'}</span>
                                                                                                                                                                                                                                </div>
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
                                                                                                                                                                                                                                    <span>${(() => {
                            if (order.table && order.table.table_number) {
                                return order.table.table_number;
                            } else {
                                const orderType = order.order_type || 'takeaway';
                                if (orderType === 'pickme' && order.pickme_ref_number) {
                                    return 'PickMe - ' + order.pickme_ref_number;
                                } else if (orderType === 'pickme') {
                                    return 'PickMe Food';
                                } else if (orderType === 'uber_eats') {
                                    return 'Uber Eats';
                                } else if (orderType === 'delivery') {
                                    return 'Delivery';
                                } else if (orderType === 'takeaway') {
                                    return 'Take Away';
                                } else {
                                    return 'Take Away';
                                }
                            }
                        })()}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="info-row">
                                                                                                                                                                                                                                    <span class="label">Cashier :</span>
                                                                                                                                                                                                                                    <span>${order.waiter ? order.waiter.name : 'Cashier'}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                            <div class="divider"></div>

                                                                                                                                                                                                                            <div class="items-header">
                                                                                                                                                                                                                                <span>Base Price</span>
                                                                                                                                                                                                                                <span>Qty Amount</span>
                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                            <div class="divider"></div>

                                                                                                                                                                                                                            ${itemsHTML}

                                                                                                                                                                                                                            <div class="divider"></div>

                                                                                                                                                                                                                            <div class="totals">
            ${(() => {
                            const vr = parseFloat('{{ $vatRate ?? 0 }}') || 0;
                            const sr = parseFloat('{{ $ssclRate ?? 0 }}') || 0;

                            let bt = 0;
                            let sa = 0;
                            let va = 0;

                            const items_t = order.order_items || order.orderItems || [];
                            items_t.forEach(item => {
                                const appliesVat = item.vat_available ? vr : 0;
                                const appliesSscl = item.sscl_available ? sr : 0;
                                const itemTotal = parseFloat(item.subtotal || (item.price * item.quantity));

                                const itemBase = itemTotal / ((1 + (appliesSscl / 100)) * (1 + (appliesVat / 100)));
                                const itemSscl = itemBase * (appliesSscl / 100);
                                const itemVat = (itemBase + itemSscl) * (appliesVat / 100);

                                bt += itemBase;
                                sa += itemSscl;
                                va += itemVat;
                            });
                            return `
                                                                                                                                                                                                                                <div class="total-row">
                                                                                                                                                                                                                                    <span>Sub Total (Base)</span>
                                                                                                                                                                                                                                    <span>${bt.toFixed(2)}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="total-row">
                                                                                                                                                                                                                                    <span>SSCL (${sr}%)</span>
                                                                                                                                                                                                                                    <span>${sa.toFixed(2)}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="total-row">
                                                                                                                                                                                                                                    <span>VAT (${vr}%)</span>
                                                                                                                                                                                                                                    <span>${va.toFixed(2)}</span>
                                                                                                                                                                                                                                </div>
                `;
                        })()}
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
                window.openClosedOrdersModal = async function () {
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
                                container.innerHTML = result.orders.map(order => {
                                    let typeDisplay = '';
                                    if (order.order_type === 'dine_in' && order.table_number !== 'N/A') {
                                        typeDisplay = `Table: ${order.table_number}`;
                                    } else if (order.order_type === 'takeaway') {
                                        typeDisplay = 'TakeAway';
                                    } else if (order.order_type === 'pickme' && order.pickme_ref_number) {
                                        typeDisplay = `PickMe - ${order.pickme_ref_number}`;
                                    } else {
                                        typeDisplay = order.order_type || 'N/A';
                                    }

                                    return `
                                                                                                                                                                                                                                                        <div class="bg-gray-700 rounded-lg p-4 mb-2 flex justify-between items-center hover:bg-gray-650 transition">
                                                                                                                                                                                                                                                            <div>
                                                                                                                                                                                                                                                                <div class="text-white font-semibold">${order.order_number}</div>
                                                                                                                                                                                                                                                                <div class="text-sm text-gray-400">
                                                                                                                                                                                                                                                                    ${typeDisplay} | ${order.items_count} items | ${order.payment_method.toUpperCase()}
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
                                                                                                                                                                                                                                                        `;
                                }).join('');
                            }

                            document.getElementById('closedOrdersModal').classList.remove('hidden');
                        }
                    } catch (error) {
                        showNotification('Error loading closed orders: ' + error.message, 'Error');
                    }
                };

                // Print Receipt (Using jsPDF and QZ Tray - same as POS payment receipt)
                window.printReceipt = async function (orderId) {
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

                            // Use the same PDF printing function as POS payment flow
                            await printReceiptWithQZ(orderData);
                            showNotification('Receipt sent to printer', 'Success');
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