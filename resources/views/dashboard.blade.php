@extends('layouts.app')

@section('title', 'Dashboard - Ravon POS')

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <!-- Welcome Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#667eea] to-[#764ba2] bg-clip-text text-transparent mb-2">
                    Welcome back, {{ Auth::user()->name }}!
                </h1>
                <p class="text-gray-600">Here's what's happening with your restaurant today.</p>
            </div>
            @if(Auth::user()->hasRole('cashier'))
            <div>
                <a href="{{ route('pos.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white text-lg font-bold rounded-xl shadow-2xl hover:shadow-purple-500/50 transform hover:scale-105 transition-all duration-200">
                    <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Open POS
                </a>
            </div>
            @endif
        </div>

        <!-- Dashboard Content Area -->
        <div class="grid grid-cols-1 gap-6">
            <!-- You can add dashboard widgets here in the future -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-purple-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="text-gray-600 text-lg">Dashboard widgets can be added here</p>
                    <p class="text-gray-400 text-sm mt-2">Use the sidebar to navigate to different sections</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection