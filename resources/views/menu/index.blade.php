@extends('layouts.app')

@section('title', 'Menu Management')

@section('content')
<div class="min-h-screen bg-gray-900 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

        <!-- Items Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($items as $item)
            <div class="item-card bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-700 hover:border-blue-500 transition" data-category="{{ $item->category_id }}">
                <!-- Item Image -->
                <div class="h-48 bg-gray-700 relative">
                    @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    @endif

                    <!-- Status Badges -->
                    <div class="absolute top-2 right-2 flex gap-2">
                        @if($item->is_featured)
                        <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-semibold rounded">Featured</span>
                        @endif
                        @if(!$item->is_available)
                        <span class="px-2 py-1 bg-red-500 text-white text-xs font-semibold rounded">Unavailable</span>
                        @endif
                    </div>
                </div>

                <!-- Item Details -->
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold text-white">{{ $item->name }}</h3>
                        <span class="text-green-400 font-bold">Rs. {{ number_format($item->price, 2) }}</span>
                    </div>

                    <p class="text-sm text-gray-400 mb-3">{{ $item->category->name }}</p>

                    @if($item->description)
                    <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $item->description }}</p>
                    @endif

                    <!-- Modifiers Count -->
                    @if($item->modifiers->count() > 0)
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2 py-1 bg-blue-900 text-blue-300 text-xs rounded">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            {{ $item->modifiers->count() }} Modifiers
                        </span>
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('menu.items.edit', $item) }}" class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition text-center">
                            Edit
                        </a>
                        <form action="{{ route('menu.items.destroy', $item) }}" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-gray-400 text-lg">No menu items found</p>
                <a href="{{ route('menu.items.create') }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Add Your First Item
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    function filterCategory(categoryId) {
        const items = document.querySelectorAll('.item-card');
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