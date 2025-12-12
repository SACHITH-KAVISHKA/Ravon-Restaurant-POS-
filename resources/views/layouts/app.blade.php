<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ravon Restaurant POS')</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ravon: {
                            primary: '#667eea',
                            primaryEnd: '#764ba2',
                            primaryHover: '#5a6fd8',
                            primaryHoverEnd: '#6a4190',
                            success: '#28a745',
                            successEnd: '#20c997',
                            danger: '#dc3545',
                            warning: '#ffc107',
                            info: '#17a2b8',
                            secondary: '#6c757d',
                            bg: '#f8f9fa',
                            surface: '#ffffff',
                            text: '#495057',
                            textMuted: '#6c757d',
                            textWhite: '#ffffff',
                            border: '#dee2e6',
                        }
                    },
                    boxShadow: {
                        'ravon-soft': '0 6px 18px rgba(102, 126, 234, 0.25)',
                        'ravon-subtle': '0 4px 10px rgba(118, 75, 162, 0.15)',
                    }
                }
            }
        }
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f8f9fa;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
        }
        
        /* Touch-friendly buttons */
        .touch-button {
            min-height: 3.5rem;
            min-width: 3.5rem;
        }
        
        /* Gradient background */
        .gradient-bg {
            background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 50%, #f8f9fa 100%);
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-ravon-bg text-ravon-text antialiased">
    @if(isset($hideNavigation) && $hideNavigation)
        @yield('content')
    @else
        <div class="min-h-screen gradient-bg">
            @include('layouts.navigation')
            
            <main class="pb-6">
                @yield('content')
            </main>
        </div>
    @endif
    
    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <script>
        // CSRF Token for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Toast Notification Function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                type === 'success' ? 'bg-gradient-to-r from-[#28a745] to-[#20c997]' : 
                type === 'error' ? 'bg-[#dc3545]' : 
                type === 'warning' ? 'bg-[#ffc107] text-gray-800' : 
                type === 'info' ? 'bg-[#17a2b8]' : 'bg-white border border-gray-200 text-gray-800 shadow-ravon-subtle'
            } text-white`;
            
            toast.innerHTML = `
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>${message}</span>
            `;
            
            document.getElementById('toast-container').appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Format currency
        function formatCurrency(amount) {
            return 'Rs. ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    </script>
    
    @stack('scripts')
</body>
</html>
