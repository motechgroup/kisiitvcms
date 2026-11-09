{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{{ $siteName }} - {{ $siteTagline }}</title>
        <link>{{ url('/') }}</link>
        <description>{{ $siteDescription }}</description>
        <language>en-ke</language>
        <pubDate>{{ now()->toRfc2822String() }}</pubDate>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ route('seo.rss') }}" rel="self" type="application/rss+xml"/>
        <generator>Kisii TV Newsroom CMS</generator>

        @foreach($articles as $article)
            <item>
                <title><![CDATA[{{ $article->title }}]]></title>
                <link>{{ route('articles.show', $article->slug) }}</link>
                <guid isPermaLink="true">{{ route('articles.show', $article->slug) }}</guid>
                <dc:creator><![CDATA[{{ $article->author->name ?? 'Kisii TV News Desk' }}]]></dc:creator>
                <category><![CDATA[{{ $article->category->name ?? 'General' }}]]></category>
                <pubDate>{{ ($article->published_at ?? $article->created_at)->toRfc2822String() }}</pubDate>
                <description><![CDATA[{{ $article->summary ?? Str::limit(strip_tags($article->content), 280) }}]]></description>
                <content:encoded><![CDATA[{!! $article->content !!}]]></content:encoded>
                @if($article->featured_image)
                    <media:content url="{{ $article->featured_image }}" medium="image">
                        <media:title><![CDATA[{{ $article->title }}]]></media:title>
                        @if($article->image_caption)
                            <media:description><![CDATA[{{ $article->image_caption }}]]></media:description>
                        @endif
                        @if($article->image_credit)
                            <media:credit><![CDATA[{{ $article->image_credit }}]]></media:credit>
                        @endif
                    </media:content>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
