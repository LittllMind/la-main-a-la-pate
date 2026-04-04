<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Vinyle Hydrodécoupé' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Styles pour le dashboard admin */
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(147, 51, 234, 0.2);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #6d28d9, #be185d);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        
        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        .vinyle-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .vinyle-table th {
            background: rgba(147, 51, 234, 0.2);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #e9d5ff;
            border-bottom: 2px solid rgba(147, 51, 234, 0.3);
        }
        
        .vinyle-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #e5e5e5;
        }
        
        .vinyle-table tr:hover {
            background: rgba(147, 51, 234, 0.1);
        }
        
        .vinyle-table tr.low-stock {
            background: rgba(239, 68, 68, 0.1);
        }
        
        .thumb-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(147, 51, 234, 0.3);
        }
        
        .no-image {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
        }
        
        .search-input::placeholder {
            color: #9ca3af;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2);
        }
        
        .page-content {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .pagination-wrapper {
            margin-top: 1.5rem;
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            color: #e5e5e5;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: rgba(147, 51, 234, 0.3);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
        }
        
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        
        .modal-content {
            background: #1e1e2e;
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            justify-content: flex-end;
        }
        
        [x-cloak] {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation simplifiée pour admin -->
    <nav class="bg-gray-800/90 backdrop-blur-sm border-b border-gray-700 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('vinyles.index') }}" class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                    📊 Dashboard Admin
                </a>
                <div class="flex items-center gap-4">
                    <a href="{{ route('kiosque.index') }}" class="text-sm hover:text-purple-400 transition">Voir le Kiosque</a>
                    <a href="{{ route('addresses.index') }}" class="text-sm hover:text-purple-400 transition">Adresses</a>
                    @auth
                        <span class="text-sm text-gray-400">{{ auth()->user()->email }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-400 hover:text-red-300 transition">Déconnexion</button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Header avec slot -->
    <header class="bg-gray-800/50 border-b border-gray-700 py-6">
        <div class="container mx-auto px-4">
            {{ $header }}
        </div>
    </header>

    <!-- Page Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        @if (session('success'))
            <div class="alert alert-success bg-green-600 text-white px-4 py-3 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error bg-red-600 text-white px-4 py-3 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 border-t border-gray-700 py-6 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            <p>© 2026 Vinyle Hydrodécoupé - Panel Admin</p>
        </div>
    </footer>

    <!-- Script Alpine pour confirmation suppression -->
    <script>
        function deleteConfirm() {
            return {
                showModal: false,
                selectedVinyle: '',
                deleteUrl: '',
                confirmDelete(id, name) {
                    this.selectedVinyle = name;
                    this.deleteUrl = '/vinyles/' + id;
                    this.showModal = true;
                },
                deleteVinyle() {
                    fetch(this.deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    }).then(() => window.location.reload());
                }
            }
        }
    </script>
</body>
</html>
