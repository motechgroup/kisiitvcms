{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    
    <!-- Homepage -->
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Categories -->
    @foreach($categories as $category)
        <url>
            <loc>{{ route('categories.show', $category->slug) }}</loc>
            <lastmod>{{ ($category->updated_at ?? now())->toAtomString() }}</lastmod>
            <changefreq>hourly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    <!-- Published Articles -->
    @foreach($articles as $article)
        <url>
            <loc>{{ route('articles.show', $article->slug) }}</loc>
            <lastmod>{{ ($article->updated_at ?? $article->published_at ?? now())->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
            @if($article->featured_image)
                <image:image>
                    <image:loc>{{ $article->featured_image }}</image:loc>
                    <image:title>{{ $article->title }}</image:title>
                    @if($article->image_caption)
                        <image:caption>{{ $article->image_caption }}</image:caption>
                    @endif
                </image:image>
            @endif
            @if($article->published_at && $article->published_at->gt(now()->subDays(2)))
                <news:news>
                    <news:publication>
                        <news:name>{{ $siteName }}</news:name>
                        <news:language>en</news:language>
                    </news:publication>
                    <news:publication_date>{{ $article->published_at->toAtomString() }}</news:publication_date>
                    <news:title>{{ $article->title }}</news:title>
                </news:news>
            @endif
        </url>
    @endforeach
</urlset>
