{{--
    Schema.org Structured Data Component
    Usage:
    @include('components.schema-org')
    @include('components.schema-org', ['type' => 'product', 'product' => $vinyle])
    @include('components.schema-org', ['type' => 'website'])
    @include('components.schema-org', ['type' => 'organization'])
--}}

@php
$baseUrl = config('app.url') ?? url('/');
$siteName = 'Fundisc';
$siteDescription = 'Vente de vinyles d\'occasion de qualité - Spécialiste reggae, funk, soul, disco et metal';
@endphp

@if (empty($type) || $type === 'website')
{{-- WebSite Schema --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "{{ $siteName }}",
    "url": "{{ $baseUrl }}",
    "description": "{{ $siteDescription }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": "{{ $baseUrl }}/kiosque?search={search_term_string}"
        },
        "query-input": "required name=search_term_string"
    }
}
</script>
@endif

@if (empty($type) || $type === 'organization')
{{-- Organization Schema --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ $siteName }}",
    "url": "{{ $baseUrl }}",
    "description": "{{ $siteDescription }}",
    "foundingDate": "2024",
    "address": {
        "@type": "PostalAddress",
        "addressCountry": "FR"
    },
    "contactPoint": {
        "@type": "ContactPoint",
        "contactType": "Customer Service",
        "availableLanguage": ["French"]
    },
    "sameAs": [
        "https://www.instagram.com/fundisc_vinyl"
    ]
}
</script>
@endif

@if (!empty($type) && $type === 'product' && !empty($product))
{{-- Product Schema for individual items --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "{{ $product->nom ?? $product->titre ?? 'Vinyle - Produit' }}",
    "image": [
        @if($product->cover_image)
        "{{ Storage::disk('public')->url($product->cover_image) }}"
        @else
        "{{ $baseUrl }}/build/assets/images/vinyl-placeholder.png"
        @endif
    ],
    "description": "Vinyle {{ $product->format ?? 'LP' }} - Genre: {{ $product->genre ?? 'Non spécifié' }} - Réf: {{ $product->reference ?? '' }}",
    "sku": "{{ $product->reference ?? $product->id }}",
    "brand": {
        "@type": "Brand",
        "name": "{{ $product->artiste ?? $product->nom ?? 'Divers Artistes' }}"
    },
    "manufacturer": {
        "@type": "Organization",
        "name": "{{ $product->label ?? 'Indépendant' }}"
    },
    "category": "{{ $product->genre ?? 'Vinyle' }}",
    "offers": {
        "@type": "Offer",
        @if(isset($product->quantite) && $product->quantite > 0)
        "availability": "https://schema.org/InStock",
        @else
        "availability": "https://schema.org/OutOfStock",
        @endif
        "price": "{{ number_format(($product->prix ?? 0) / 100, 2, '.', '') }}",
        "priceCurrency": "EUR",
        "url": "{{ $baseUrl }}/kiosque/{{ $product->id }}"
    },
    "aggregateRating": @if(isset($product->prix_moyen) && $product->prix_moyen)
    {
        "@type": "AggregateRating",
        "ratingValue": "{{ number_format($product->prix_moyen / ($product->prix ?? 1), 1) }}",
        "reviewCount": "1"
    }
    @else
    null
    @endif
}
</script>
@endif

@if (!empty($type) && $type === 'catalog' && !empty($items))
{{-- ItemList + Products Schema for catalog --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Catalogue Vinyles - {{ $siteName }}",
    "description": "Découvrez notre collection de vinyles d'occasion",
    "itemListElement": [
    @foreach($items as $index => $item)
    {
        "@type": "ListItem",
        "position": {{ $index + 1 }},
        "item": {
            "@type": "Product",
            "name": "{{ $item['artiste'] ?? $item['nom'] ?? 'Vinyle' }} - {{ $item['modele'] ?? 'Modèle inconnu' }}",
            "image": "{{ $item['image'] ?? '' }}",
            "description": "Vinyle d'occasion - Genre: {{ $item['genre'] ?? 'Divers' }}",
            "sku": "{{ $item['id'] }}",
            "brand": {
                "@type": "Brand",
                "name": "{{ $item['artiste'] ?? 'Artiste' }}"
            },
            "offers": {
                "@type": "Offer",
                @if(($item['quantite'] ?? 0) > 0)
                "availability": "https://schema.org/InStock",
                @else
                "availability": "https://schema.org/OutOfStock",
                @endif
                "price": "{{ number_format(($item['prix'] ?? 0) / 100, 2, '.', '') }}",
                "priceCurrency": "EUR",
                "url": "{{ $baseUrl }}/kiosque"
            }
        }
    }@if(!$loop->last),@endif
    @endforeach
    ]
}
</script>
@endif

@if (!empty($type) && $type === 'webpage' && !empty($title))
{{-- WebPage Schema --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "{{ $title }}",
    "description": "{{ $description ?? $siteDescription }}",
    "url": "{{ Request::url() }}",
    "isPartOf": {
        "@type": "WebSite",
        "name": "{{ $siteName }}",
        "url": "{{ $baseUrl }}"
    }
}
</script>
@endif