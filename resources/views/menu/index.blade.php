@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')
<div class="min-h-screen bg-gray-900 flex">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white">Menu Management</h1>
                    <p class="text-gray-400 mt-1">Manage your restaurant menu items, categories, and modifiers</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('menu.items.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New Item
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 p-4 bg-green-900 border border-green-700 text-green-100 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <!-- Categories Tabs -->
        <div class="bg-gray-800 rounded-lg shadow-lg mb-6">
            <div class="border-b border-gray-700">
                <div class="flex overflow-x-auto">
                    <button onclick="filterCategory('all')" class="category-filter px-6 py-4 text-white bg-blue-600 font-semibold border-b-2 border-blue-500 whitespace-nowrap">
                        All Items
                    </button>
                    @foreach($categories as $category)
                    <button onclick="filterCategory({{ $category->id }})" class="category-filter px-6 py-4 text-gray-400 hover:text-white font-semibold border-b-2 border-transparent hover:border-gray-600 whitespace-nowrap">
                        {{ $category->name }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-900 border-b border-gray-700">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($items as $item)
                        <tr class="item-row hover:bg-gray-700 transition" data-category="{{ $item->category_id }}">
                            <!-- Item Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12 mr-4">
                                        @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="h-12 w-12 rounded-lg object-cover">
                                        @else
                                        <div class="h-12 w-12 rounded-lg bg-gray-700 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-white">{{ $item->name }}</div>
                                        @if($item->description)
                                        <div class="text-xs text-gray-400 mt-1">{{ Str::limit($item->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-300">{{ $item->category->name }}</span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('menu.items.edit', $item) }}" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('menu.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-gray-400 text-lg">No menu items found</p>
                                <a href="{{ route('menu.items.create') }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Add Your First Item
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function filterCategory(categoryId) {
        const items = document.querySelectorAll('.item-row');
        const filters = document.querySelectorAll('.category-filter');

        // Update filter buttons
        filters.forEach(btn => {
            btn.classList.remove('bg-blue-600', 'border-blue-500', 'text-white');
            btn.classList.add('text-gray-400', 'border-transparent');
        });
        event.target.classList.remove('text-gray-400', 'border-transparent');
        event.target.classList.add('bg-blue-600', 'border-blue-500', 'text-white');

        // Filter items
        items.forEach(item => {
            if (categoryId === 'all' || item.dataset.category == categoryId) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }
</script>
@endsection