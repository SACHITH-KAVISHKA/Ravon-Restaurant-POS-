@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
    <div class="min-h-screen bg-gray-50 flex">
        <x-sidebar />
        <div class="flex-1 py-6 w-full">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div>
                        <h1
                            class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent">
                            System Settings
                        </h1>
                        <p class="text-gray-800-muted mt-1">Manage core system values globally</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-600/20 border border-ravon-success text-green-600 rounded-lg font-semibold">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Form -->
                <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                    <form action="{{ route('settings.store') }}" method="POST">
                        @csrf

                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-8 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                            <!-- Left Side: VAT -->
                            <div class="md:pr-8 pt-4 md:pt-0">
                                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <span
                                        class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                    VAT Settings
                                </h2>
                                <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200 mb-4">
                                    <label class="block text-sm font-semibold text-gray-800-muted mb-2">VAT Percentage
                                        (%)</label>
                                    <input type="number" step="0.01" name="vat"
                                        value="{{ old('vat', $setting?->vat ?? '') }}" placeholder="e.g. 15.00"
                                        class="w-full px-4 py-2 bg-white text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 @error('vat') border-red-600 @enderror">
                                    @error('vat')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    @if (!empty($setting) && $setting->vat !== null)
                                        <p class="text-sm mt-2 font-medium text-indigo-600">Current VAT is set to
                                            {{ $setting->vat }}%
                                        </p>
                                    @else
                                        <p class="text-sm mt-2 text-gray-500">No VAT value set yet. You can create it now.</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Side: SSCL -->
                            <div class="md:pl-8 pt-6 md:pt-0">
                                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <span
                                        class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-teal-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 14l6-6m-5.5.5h.01m4.49 4.49h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                    SSCL Settings
                                </h2>
                                <div class="bg-teal-50 p-4 rounded-lg border border-teal-200 mb-4">
                                    <label class="block text-sm font-semibold text-gray-800-muted mb-2">SSCL Percentage
                                        (%)</label>
                                    <input type="number" step="0.01" name="sscl"
                                        value="{{ old('sscl', $setting?->sscl ?? '') }}" placeholder="e.g. 2.50"
                                        class="w-full px-4 py-2 bg-white text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500 @error('sscl') border-red-600 @enderror">
                                    @error('sscl')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    @if (!empty($setting) && $setting->sscl !== null)
                                        <p class="text-sm mt-2 font-medium text-teal-600">Current SSCL is set to
                                            {{ $setting->sscl }}%
                                        </p>
                                    @else
                                        <p class="text-sm mt-2 text-gray-500">No SSCL value set yet. You can create it now.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- VAT Reg No -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                VAT Registration
                            </h2>
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">VAT Registration Number</label>
                                <input type="text" name="vat_reg_no"
                                    value="{{ old('vat_reg_no', $setting?->vat_reg_no ?? '') }}"
                                    placeholder="e.g. 103803281-7000"
                                    class="w-full px-4 py-2 bg-white text-gray-800 rounded-lg border border-gray-300 focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500 @error('vat_reg_no') border-red-600 @enderror">
                                @error('vat_reg_no')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                @if (!empty($setting?->vat_reg_no))
                                    <p class="text-sm mt-2 font-medium text-orange-600">Current: {{ $setting->vat_reg_no }}</p>
                                @else
                                    <p class="text-sm mt-2 text-gray-500">No VAT Reg No set yet. This appears on printed receipts.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex gap-3 mt-8 justify-end border-t border-gray-100 pt-6">
                            <button type="submit"
                                class="px-8 py-2.5 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-lg hover:shadow-purple-500/50 transition font-semibold">
                                {{ isset($setting) && ($setting->vat !== null || $setting->sscl !== null) ? 'Update Settings' : 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
@endsection