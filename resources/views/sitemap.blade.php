{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    @php
        $lastModified = now()->toAtomString();

        $staticPages = [
            ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/models'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/portofolio'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/joinacademy'), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => url('/login'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => url('/register'), 'changefreq' => 'monthly', 'priority' => '0.4'],
        ];

        // Tabel SQL = models; status = available|unavailable; PK = id_model; tanpa updated_at
        try {
            $models = \Illuminate\Support\Facades\Schema::hasTable('models')
                ? \Illuminate\Support\Facades\DB::table('models')->where('status', 'available')->get()
                : collect();
        } catch (\Throwable $e) {
            $models = collect();
        }
    @endphp

    @foreach($staticPages as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <lastmod>{{ $lastModified }}</lastmod>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
    @endforeach

    @foreach($models as $model)
    <url>
        <loc>{{ url('/portofolio/' . $model->id_model) }}</loc>
        <lastmod>{{ $lastModified }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        @if(!empty($model->photo))
        <image:image>
            <image:loc>{{ asset('storage/' . ltrim($model->photo, '/')) }}</image:loc>
            <image:title>{{ $model->nama_model }} - Academy Next Top Model</image:title>
            <image:caption>{{ $model->nama_model }} professional model portfolio</image:caption>
        </image:image>
        @endif
    </url>
    @endforeach

</urlset>
