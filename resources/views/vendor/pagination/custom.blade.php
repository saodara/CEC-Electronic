@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        @if ($paginator->onFirstPage())
            <span class="btn secondary pager-nav disabled" aria-disabled="true">&larr; Back</span>
        @else
            <a class="btn secondary pager-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Back</a>
        @endif

        <div class="pager-pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager-page active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pager-page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="btn pager-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
        @else
            <span class="btn pager-nav disabled" aria-disabled="true">Next &rarr;</span>
        @endif
    </nav>

    <p class="pager-summary">
        Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </p>
@endif
