@extends('layouts.app')

@section('title', 'Category Management')

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
                    <h1 class="text-3xl font-bold text-white">Category Management</h1>
                    <p class="text-gray-400 mt-1">Manage food and beverage categories</p>
                </div>
                <button onclick="openAddCategoryModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Category
                </button>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-4 p-4 bg-green-900 border border-green-700 text-green-100 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <!-- Categories Table -->
        <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-900 border-b border-gray-700">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Items Count</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Display Order</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($categories as $category)
                        <tr class="hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-white">{{ $category->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-300">{{ $category->description ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900 text-blue-300">
                                    {{ $category->items->count() }} items
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-300">{{ $category->display_order ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick='editCategory({{ $category->id }}, "{{ addslashes($category->name) }}", "{{ addslashes($category->description ?? '') }}", {{ $category->display_order ?? 0 }})' class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('menu.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Are you sure? This will affect all items in this category!')">
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
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <p class="text-gray-400 text-lg">No categories found</p>
                                <button onclick="openAddCategoryModal()" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Add Your First Category
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-white" id="modalTitle">Add Category</h2>
            <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="categoryForm" method="POST" action="{{ route('menu.categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="category_id" id="categoryId">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Category Name *</label>
                    <input type="text" name="name" id="categoryName" required class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Description</label>
                    <textarea name="description" id="categoryDescription" rows="3" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">Display Order</label>
                    <input type="number" name="display_order" id="categoryOrder" value="0" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg border border-gray-600 focus:outline-none focus:border-blue-500">
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeCategoryModal()" class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Category
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddCategoryModal() {
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('categoryForm').action = '{{ route("menu.categories.store") }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryDescription').value = '';
        document.getElementById('categoryOrder').value = '0';
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function editCategory(id, name, description, order) {
        document.getElementById('modalTitle').textContent = 'Edit Category';
        document.getElementById('categoryForm').action = '/menu/categories/' + id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('categoryId').value = id;
        document.getElementById('categoryName').value = name;
        document.getElementById('categoryDescription').value = description || '';
        document.getElementById('categoryOrder').value = order || 0;
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.add('hidden');
    }
</script>
@endsection