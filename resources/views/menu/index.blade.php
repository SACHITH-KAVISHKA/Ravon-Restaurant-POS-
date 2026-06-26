@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')
    <div class="h-screen bg-gray-50 flex overflow-hidden">
        <!-- Sidebar Component -->
        <x-sidebar />

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Header - Fixed height -->
            <div class="flex-shrink-0 p-6 pb-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-center">
                    <div>
                        <h1
                            class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            Menu Management</h1>
                        <p class="text-gray-600 text-sm mt-1">Manage your restaurant menu items, categories, and modifiers
                        </p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <div class="relative">
                            <label for="menu-search" class="sr-only">Search items</label>
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35m1.6-4.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input id="menu-search" type="search" placeholder="Search items..."
                                class="w-full sm:w-64 pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300">
                        </div>
                        <a href="{{ route('menu.items.create') }}"
                            class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold px-5 py-2 rounded-lg transition duration-200 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add New Item
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div
                    class="flex-shrink-0 mx-6 mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Categories Tabs - Fixed height -->
            <div class="flex-shrink-0 mx-6 mb-4 bg-white rounded-lg shadow-md border border-gray-200">
                <div class="border-b border-gray-200">
                    <div class="category-tabs-container">
                        <button onclick="filterCategory('all', this)"
                            class="category-filter px-5 py-3 text-purple-600 bg-purple-50 font-semibold border-b-2 border-purple-600 whitespace-nowrap flex-shrink-0 text-sm">
                            All Items
                        </button>
                        @foreach ($categories as $category)
                            <button onclick="filterCategory({{ $category->id }}, this)"
                                class="category-filter px-5 py-3 text-gray-600 hover:text-purple-600 font-semibold border-b-2 border-transparent hover:border-purple-300 whitespace-nowrap flex-shrink-0 transition text-sm">
                                {{ $category->name }}
                            </button>
                        @endforeach
                        <button onclick="filterCategory('inactive', this)"
                            class="category-filter px-5 py-3 text-gray-600 hover:text-purple-600 font-semibold border-b-2 border-transparent hover:border-purple-300 whitespace-nowrap flex-shrink-0 transition text-sm">
                            Inactive
                        </button>
                    </div>
                </div>
            </div>

            <style>
                .category-tabs-container {
                    display: flex;
                    overflow-x: auto;
                    scrollbar-width: thin;
                    scrollbar-color: #667eea #f3f4f6;
                    -ms-overflow-style: auto;
                }

                .category-tabs-container::-webkit-scrollbar {
                    height: 6px;
                }

                .category-tabs-container::-webkit-scrollbar-track {
                    background: #f3f4f6;
                    border-radius: 3px;
                }

                .category-tabs-container::-webkit-scrollbar-thumb {
                    background: linear-gradient(to right, #667eea, #764ba2);
                    border-radius: 3px;
                }

                .category-tabs-container::-webkit-scrollbar-thumb:hover {
                    background: linear-gradient(to right, #5a6fd6, #6a4190);
                }

                .items-table-container {
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: #667eea #f3f4f6;
                }

                .items-table-container::-webkit-scrollbar {
                    width: 8px;
                }

                .items-table-container::-webkit-scrollbar-track {
                    background: #f3f4f6;
                    border-radius: 4px;
                }

                .items-table-container::-webkit-scrollbar-thumb {
                    background: linear-gradient(to bottom, #667eea, #764ba2);
                    border-radius: 4px;
                }

                .items-table-container::-webkit-scrollbar-thumb:hover {
                    background: linear-gradient(to bottom, #5a6fd6, #6a4190);
                }
            </style>

            <!-- Items Table - Scrollable section that fills remaining space -->
            <div
                class="flex-1 mx-6 mb-6 bg-white rounded-lg shadow-md border border-gray-200 flex flex-col overflow-hidden">
                <!-- Table Header - Fixed -->
                <div class="flex-shrink-0 bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                    style="width: 50%;">Item Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-white uppercase tracking-wider"
                                    style="width: 30%;">Category</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-white uppercase tracking-wider"
                                    style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- Table Body - Scrollable -->
                <div class="flex-1 items-table-container">
                    <table class="w-full">
                        <tbody class="divide-y divide-gray-200">
                            @forelse($items as $item)
                                <tr class="item-row hover:bg-purple-50 transition" data-category="{{ $item->category_id }}"
                                    data-search="{{ Str::lower($item->name . ' ' . ($item->description ?? '')) }}">
                                    <!-- Item Name -->
                                    <td class="px-6 py-3" style="width: 50%;">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                @if ($item->image)
                                                    <img src="{{ asset('storage/' . $item->image) }}"
                                                        alt="{{ $item->name }}" class="h-10 w-10 rounded-lg object-cover">
                                                @else
                                                    <div
                                                        class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-800">{{ $item->name }}</div>
                                                @if ($item->description)
                                                    <div class="text-xs text-gray-500">
                                                        {{ Str::limit($item->description, 40) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category -->
                                    <td class="px-6 py-3" style="width: 30%;">
                                        <span class="text-sm text-gray-700">{{ $item->category->name }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3" style="width: 20%;">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('menu.items.edit', $item) }}"
                                                class="inline-flex items-center px-2.5 py-1.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-md transition"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('menu.items.destroy', $item) }}" method="POST"
                                                onsubmit="event.preventDefault(); confirmDelete(this, 'item');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-2.5 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                                    title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="menu-active-empty-row">
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-gray-600 text-lg">No menu items found</p>
                                        <a href="{{ route('menu.items.create') }}" class="inline-block mt-4 btn-primary">
                                            Add Your First Item
                                        </a>
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Inactive Menu Items -->
                            @foreach($inactiveItems as $item)
                                <tr class="item-row hover:bg-purple-50 transition hidden" data-category="inactive" data-inactive="true"
                                    data-search="{{ Str::lower($item->name . ' ' . ($item->description ?? '')) }}">
                                    <!-- Item Name -->
                                    <td class="px-6 py-3" style="width: 50%;">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                @if ($item->image)
                                                    <img src="{{ asset('storage/' . $item->image) }}"
                                                        alt="{{ $item->name }}" class="h-10 w-10 rounded-lg object-cover">
                                                @else
                                                    <div
                                                        class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-800">{{ $item->name }}</div>
                                                @if ($item->description)
                                                    <div class="text-xs text-gray-500">
                                                        {{ Str::limit($item->description, 40) }}</div>
                                                @endif
                                                <div class="text-xs text-red-500 mt-0.5 font-medium">Status: Inactive Item</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category -->
                                    <td class="px-6 py-3" style="width: 30%;">
                                        <span class="text-sm text-gray-700">{{ $item->category->name }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3" style="width: 20%;">
                                        <div class="flex items-center justify-center gap-2">
                                            <form action="{{ route('menu.items.activate', $item->id) }}" method="POST"
                                                onsubmit="event.preventDefault(); confirmActivate(this, 'item');">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs font-semibold gap-1"
                                                    title="Activate">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Activate
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Inactive Item Portions -->
                            @foreach($inactivePortions as $portion)
                                @php
                                    $item = $portion->item;
                                @endphp
                                @if($item)
                                    <tr class="item-row hover:bg-purple-50 transition hidden" data-category="inactive" data-inactive="true"
                                        data-search="{{ Str::lower($item->name . ' ' . $portion->name) }}">
                                        <!-- Item Name (Parent + Portions Tree) -->
                                        <td class="px-6 py-3" style="width: 50%;">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 h-10 w-10 mr-3 mt-1">
                                                    @if ($item->image)
                                                        <img src="{{ asset('storage/' . $item->image) }}"
                                                            alt="{{ $item->name }}" class="h-10 w-10 rounded-lg object-cover">
                                                    @else
                                                        <div
                                                            class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                                            <svg class="w-5 h-5 text-gray-400" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-800">{{ $item->name }}</div>
                                                    <div class="text-xs font-semibold font-mono mt-1 leading-relaxed">
                                                        @php
                                                            // Get all portions (both active and inactive) of this item
                                                            $allPortions = $item->modifiers;
                                                            $total = count($allPortions);
                                                        @endphp
                                                        @foreach($allPortions as $idx => $p)
                                                            @php
                                                                $isLast = $idx === $total - 1;
                                                                $prefix = $isLast ? '└── ' : '├── ';
                                                                $isPortionInactive = $p->status == 0;
                                                            @endphp
                                                            @if($isPortionInactive)
                                                                <span class="text-red-500 font-bold">
                                                                    {{ $prefix }}{{ $p->name }} (Inactive)
                                                                </span>
                                                            @else
                                                                <span class="text-gray-500 font-normal">
                                                                    {{ $prefix }}{{ $p->name }} (Active)
                                                                </span>
                                                            @endif
                                                            @if(!$isLast)<br>@endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Category -->
                                        <td class="px-6 py-3" style="width: 30%;">
                                            <span class="text-sm text-gray-700">{{ $item->category->name }}</span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-3" style="width: 20%;">
                                            <div class="flex items-center justify-center gap-2">
                                                <form action="{{ route('menu.modifiers.activate', $portion->id) }}" method="POST"
                                                    onsubmit="event.preventDefault(); confirmActivate(this, 'portion');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs font-semibold gap-1"
                                                        title="Activate">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Activate
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            <tr id="menu-inactive-empty-row" class="hidden">
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-gray-600 text-lg">No inactive menu items or portions found</p>
                                </td>
                            </tr>

                            <tr id="menu-search-empty" class="hidden">
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6m-6 4h6m-6 4h3" />
                                    </svg>
                                    <p class="text-gray-600 text-lg">No items match your search</p>
                                    <p class="text-gray-500 text-sm mt-1">Try a different keyword or clear the search.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let activeCategory = 'all';
        let searchQuery = '';

        function applyFilters() {
            const items = document.querySelectorAll('.item-row');
            let visibleCount = 0;

            items.forEach(item => {
                const isRowInactive = item.dataset.inactive === 'true';
                
                let matchesCategory = false;
                if (activeCategory === 'inactive') {
                    matchesCategory = isRowInactive;
                } else {
                    matchesCategory = !isRowInactive && (activeCategory === 'all' || item.dataset.category == activeCategory);
                }

                const haystack = item.dataset.search || '';
                const matchesSearch = searchQuery === '' || haystack.includes(searchQuery);

                if (matchesCategory && matchesSearch) {
                    item.classList.remove('hidden');
                    visibleCount += 1;
                } else {
                    item.classList.add('hidden');
                }
            });

            const activeEmptyRow = document.getElementById('menu-active-empty-row');
            const inactiveEmptyRow = document.getElementById('menu-inactive-empty-row');
            const searchEmptyRow = document.getElementById('menu-search-empty');

            if (activeCategory === 'inactive') {
                if (activeEmptyRow) activeEmptyRow.classList.add('hidden');
                if (inactiveEmptyRow) {
                    inactiveEmptyRow.classList.toggle('hidden', visibleCount !== 0);
                }
                if (searchEmptyRow) searchEmptyRow.classList.add('hidden');
            } else {
                if (inactiveEmptyRow) inactiveEmptyRow.classList.add('hidden');
                
                if (searchQuery !== '') {
                    if (activeEmptyRow) activeEmptyRow.classList.add('hidden');
                    if (searchEmptyRow) {
                        searchEmptyRow.classList.toggle('hidden', visibleCount !== 0);
                    }
                } else {
                    if (searchEmptyRow) searchEmptyRow.classList.add('hidden');
                    if (activeEmptyRow) {
                        activeEmptyRow.classList.toggle('hidden', visibleCount !== 0);
                    }
                }
            }
        }

        function filterCategory(categoryId, buttonEl) {
            activeCategory = categoryId;
            const filters = document.querySelectorAll('.category-filter');

            // Update filter buttons
            filters.forEach(btn => {
                btn.classList.remove('bg-purple-50', 'border-purple-600', 'text-purple-600');
                btn.classList.add('text-gray-600', 'border-transparent');
            });
            if (buttonEl) {
                buttonEl.classList.remove('text-gray-600', 'border-transparent');
                buttonEl.classList.add('bg-purple-50', 'border-purple-600', 'text-purple-600');
            }

            applyFilters();
        }

        function confirmDelete(form, type) {
            Swal.fire({
                title: 'Delete ' + (type === 'item' ? 'Item' : 'Portion') + '?',
                text: 'This will move the ' + type + ' to the Inactive list. It can be restored later.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#764ba2',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-xl shadow-lg border border-gray-150',
                    confirmButton: 'rounded-lg px-4 py-2 text-white font-bold',
                    cancelButton: 'rounded-lg px-4 py-2 text-white font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function confirmActivate(form, type) {
            Swal.fire({
                title: 'Activate ' + (type === 'item' ? 'Item' : 'Portion') + '?',
                text: 'This will restore the ' + type + ' and make it active throughout the POS system.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Yes, activate it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'rounded-xl shadow-lg border border-gray-150',
                    confirmButton: 'rounded-lg px-4 py-2 text-white font-bold',
                    cancelButton: 'rounded-lg px-4 py-2 text-white font-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('menu-search');
            if (!searchInput) {
                return;
            }

            searchInput.addEventListener('input', (event) => {
                searchQuery = event.target.value.trim().toLowerCase();
                applyFilters();
            });
        });
    </script>
@endsection
