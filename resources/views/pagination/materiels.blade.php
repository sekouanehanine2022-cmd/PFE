<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top">
    <small class="text-muted">
        Affichage {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} sur {{ $paginator->total() }} {{ $libelle }}
    </small>
    <div class="mw-100 overflow-auto">
        {{ $paginator->onEachSide(1)->links('pagination.comptes-utilisateurs') }}
    </div>
</div>
