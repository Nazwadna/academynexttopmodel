{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Halaman publik utama --}}
    <url>
        <loc>{{ url('/models') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/portofolio') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ url('/joinacademy') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc>{{ url('/login') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>
    <url>
        <loc>{{ url('/register') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>

    {{-- Detail model publik: /portofolio/{id_model} --}}
    @php
        // Tabel SQL import = `models`; status enum = available|unavailable
        // PK = id_model (bukan id); tidak ada updated_at
        try {
            $models = \Illuminate\Support\Facades\Schema::hasTable('models')
                ? \Illuminate\Support\Facades\DB::table('models')->where('status', 'available')->get()
                : collect();
        } catch (\Throwable $e) {
            $models = collect();
        }
    @endphp

    @foreach($models as $model)
    <url>
        <loc>{{ url('/portofolio/' . $model->id_model) }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

</urlset>
