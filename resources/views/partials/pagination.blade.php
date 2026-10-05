@if ($paginator->hasPages())
    <nav class="pager" aria-label="Halaman">
        <span class="sub">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>
        <div class="row">
            @if ($paginator->onFirstPage())
                <span class="btn sm" aria-disabled="true">‹ Sebelumnya</span>
            @else
                <a class="btn sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a>
            @endif
            @if ($paginator->hasMorePages())
                <a class="btn sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya ›</a>
            @else
                <span class="btn sm" aria-disabled="true">Berikutnya ›</span>
            @endif
        </div>
    </nav>
@endif
