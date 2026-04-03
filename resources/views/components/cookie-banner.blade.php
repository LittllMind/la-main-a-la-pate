{{-- resources/views/components/cookie-banner.blade.php --}}
@if(!session('cookies_accepted'))
<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-4 z-50">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
        <p class="text-sm">
            🔒 Ce site utilise uniquement les <strong>cookies essentiels</strong> 
            (panier, sécurité). Aucun tracking publicitaire.
            <a href="{{ route('privacy') }}" class="underline">En savoir plus</a>
        </p>
        <button onclick="acceptCookies()" class="btn btn-primary whitespace-nowrap">
            J'accepte
        </button>
    </div>
</div>

<script>
function acceptCookies() {
    fetch('{{ route('cookies.accept') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(() => {
        document.getElementById('cookie-banner').remove();
    });
}
</script>
@endif
