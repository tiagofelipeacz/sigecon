@extends('layouts.sigecon')
@section('title', 'Anexos - SIGECON')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Carbon;
@endphp

@section('content')
<style>
  /* ===== Estilos exclusivos da página de Anexos (prefixo .ax-) ===== */
  .ax-layout{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .ax-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .ax-body{ padding:12px; }                 /* padding menor */
  .ax-card--toolbar .ax-body{ padding:10px;}/* toolbar mais compacta */

  /* Toolbar como GRID para evitar alturas “elásticas” */
  .ax-toolbar{
    display:grid;
    grid-template-columns:auto 1fr auto auto; /* [Novo] [Busca] [Select] [Filtrar] */
    gap:10px;
    align-items:center;
    min-height:0;                               /* garante altura mínima */
  }
  @media (max-width: 900px){
    .ax-toolbar{ grid-template-columns:1fr; }   /* empilha no mobile */
  }

  .ax-input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:8px 11px; font-size:13px; }
  .ax-btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:7px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; font-size:13px; }
  .ax-btn:hover{ background:#f9fafb; }

  /* Botão 100% preto SEM efeito/hover/transition */
  .ax-btn--primary{
    background:#111827; border-color:#111827; color:#fff;
    transition:none; box-shadow:none;
  }
  .ax-btn--primary:hover,
  .ax-btn--primary:active,
  .ax-btn--primary:focus{ background:#111827; border-color:#111827; color:#fff; }

  .ax-btn--icon{ padding:8px; width:36px; justify-content:center; }
  .ax-btn--sm{ padding:5px 8px; font-size:12px; border-radius:7px; }

  .ax-xscroll{ overflow-x:auto; }
  .ax-table{ width:100%; border-collapse:collapse; }
  .ax-table thead th{ font-size:12px; color:#6b7280; text-align:left; padding:9px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  .ax-table tbody td{ font-size:14px; padding:12px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
  .ax-table tr:hover td{ background:#fcfcfd; }

  .ax-chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:2px 10px; border:1px solid #e5e7eb; background:#fff; }
  .ax-chip--blue{ background:#eef2ff; color:#1e3a8a; border-color:#e0e7ff; }
  .ax-muted{ color:#6b7280; font-size:12px; }

  .ax-toggle-form{ display:inline-block; }
  .ax-status-btn{
    display:inline-flex; align-items:center; justify-content:center;
    width:20px; height:20px; border-radius:999px; border:1px solid #e5e7eb;
    background:#fff; cursor:pointer; padding:0; line-height:1;
  }
  .ax-status-btn.ok{ background:#10b98122; border-color:#34d399; }
  .ax-status-btn.no{ background:#f9731622; border-color:#fb923c; }
  .ax-status-btn i{ width:14px; height:14px; }
  .ax-status-btn:focus{ outline:2px solid #dbeafe; outline-offset:2px; }

  .ax-title a, .ax-title span{
    font-size:14px; line-height:1.35; font-weight:600; color:#111827;
    text-decoration:underline; text-underline-offset:2px; word-break:break-word;
  }
  /* Impede mudar de cor após clicar */
  .ax-title a:visited,
  .ax-title a:hover,
  .ax-title a:active { color:#111827; }

  .ax-title-sub{ margin-top:2px; }

  .ax-w-id{ width:70px; }
  .ax-w-tipo{ width:110px; }
  .ax-w-pub{ width:170px; }
  .ax-w-cad{ width:120px; }
  .ax-w-ativo{ width:80px; }
  .ax-w-rest{ width:90px; }
  .ax-w-acoes{ width:140px; }
</style>


<div class="ax-layout">
  {{-- Menu lateral (seu partial) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'anexos'
    ])
  </div>

  <div class="ax-grid" style="display:grid; gap:14px;">
    {{-- Filtros / Ações --}}
    <div class="ax-card ax-card--toolbar">
  <div class="ax-body">
    <form method="get" action="{{ route('admin.concursos.anexos.index', $concurso) }}" class="ax-toolbar">

      <a href="{{ route('admin.concursos.anexos.create', $concurso) }}" class="ax-btn ax-btn--primary">
        <i data-lucide="plus"></i> Novo anexo
      </a>

      <div style="position:relative">
        <i data-lucide="search" style="position:absolute; left:10px; top:8px; width:18px; height:18px; color:#6b7280"></i>
        <input type="text" name="q" class="ax-input" placeholder="Buscar por título ou grupo"
               value="{{ $q ?? '' }}" style="padding-left:34px;">
      </div>

      <select name="ativo" class="ax-input">
        <option value="">Ativo: Todos</option>
        <option value="1" @selected(($ativo ?? '')==='1')>Somente ativos</option>
        <option value="0" @selected(($ativo ?? '')==='0')>Somente inativos</option>
      </select>

      <button class="ax-btn" type="submit">
        <i data-lucide="sliders-horizontal"></i> Filtrar
      </button>
    </form>
   </div>



    {{-- Listagem --}}
    <div class="ax-card">
      <div class="ax-body ax-xscroll">
        <table class="ax-table">
          <thead>
            <tr>
              <th class="ax-w-id">ID</th>
              <th>Título</th>
              <th class="ax-w-tipo">Tipo</th>
              <th class="ax-w-pub">Publicação</th>
              <th class="ax-w-cad">Cadastro</th>
              <th>Grupo</th>
              <th class="ax-w-ativo">Ativo</th>
              <th class="ax-w-rest">Restrito</th>
              <th class="ax-w-acoes">Ações</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            @php
              $tipo = (string)($r->tipo ?? '');
              $path = $r->arquivo_path ?? $r->arquivo ?? $r->path ?? null;
              $url  = null;

              if ($tipo === 'link') {
                  $url = $r->link_url ?? $r->url ?? null;
              } else {
                  // Sempre usa a rota interna para abrir arquivo (funciona mesmo sem symlink)
                  $url = route('admin.concursos.anexos.open', [$concurso, $r->id]);
              }

              $pubIni = $r->visivel_de  ?? null;
              $pubFim = $r->visivel_ate ?? null;

              $ind = isset($r->tempo_indeterminado)
                    ? (int)$r->tempo_indeterminado === 1
                    : (empty($pubIni) && empty($pubFim));

              if ($ind) {
                  $pubView = '<span class="ax-chip ax-chip--blue"><i data-lucide="calendar"></i> Tempo indeterminado</span>';
              } else {
                  $partes = [];
                  if ($pubIni) $partes[] = Carbon::parse($pubIni)->format('d/m/Y');
                  if ($pubFim) $partes[] = Carbon::parse($pubFim)->format('d/m/Y');
                  $pubView = e(implode(' - ', $partes)) ?: '—';
              }

              $cadStr = $r->created_at ? Carbon::parse($r->created_at)->format('d/m/Y') : '—';

              $ativoBool    = (int)($r->ativo ?? 0) === 1;
              $restritoBool = (int)($r->restrito ?? $r->privado ?? 0) === 1;
              $grupo        = trim((string)($r->grupo ?? '')) ?: '—';
              $legenda      = trim((string)($r->legenda ?? ''));
            @endphp
            <tr>
              <td>{{ $r->id }}</td>

              <td>
                <div class="ax-title" style="max-width:100%">
                  @if($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener">{{ $r->titulo }}</a>
                  @else
                    <span>{{ $r->titulo }}</span>
                  @endif
                </div>
                @if($legenda !== '')
                  <div class="ax-muted ax-title-sub">{{ $legenda }}</div>
                @endif
              </td>

              <td>
                <span class="ax-chip ax-chip--blue">
                  <i data-lucide="{{ $tipo === 'link' ? 'link' : 'file-text' }}"></i>
                  {{ $tipo === 'link' ? 'Link' : 'Arquivo' }}
                </span>
              </td>

              <td>{!! $pubView !!}</td>
              <td>{{ $cadStr }}</td>
              <td>{{ $grupo }}</td>

              {{-- Toggle Ativo --}}
              <td>
                <form class="ax-toggle-form" method="post" action="{{ route('admin.concursos.anexos.toggle-ativo', [$concurso, $r->id]) }}">
                  @csrf @method('patch')
                  <button class="ax-status-btn {{ $ativoBool ? 'ok' : 'no' }}" type="submit"
                          title="Clique para {{ $ativoBool ? 'desativar' : 'ativar' }}" aria-pressed="{{ $ativoBool ? 'true' : 'false' }}">
                    @if($ativoBool) <i data-lucide="check"></i> @else <i data-lucide="x"></i> @endif
                  </button>
                </form>
              </td>

              {{-- Toggle Restrito --}}
              <td>
                <form class="ax-toggle-form" method="post" action="{{ route('admin.concursos.anexos.toggle-restrito', [$concurso, $r->id]) }}">
                  @csrf @method('patch')
                  <button class="ax-status-btn {{ $restritoBool ? 'ok' : 'no' }}" type="submit"
                          title="Clique para {{ $restritoBool ? 'tornar público' : 'tornar restrito' }}" aria-pressed="{{ $restritoBool ? 'true' : 'false' }}">
                    @if($restritoBool) <i data-lucide="lock"></i> @else <i data-lucide="unlock"></i> @endif
                  </button>
                </form>
              </td>

              <td>
                <div style="display:flex; gap:6px; flex-wrap:wrap">
                  <a class="ax-btn ax-btn--sm" href="{{ route('admin.concursos.anexos.edit', [$concurso, $r->id]) }}">
                    <i data-lucide="pencil"></i> Editar
                  </a>
                  <form method="post"
                        action="{{ route('admin.concursos.anexos.destroy', [$concurso, $r->id]) }}"
                        onsubmit="return confirm('Remover este anexo?')">
                    @csrf @method('delete')
                    <button class="ax-btn ax-btn--sm" style="border-color:#fecaca; background:#fee2e2; color:#991b1b">
                      <i data-lucide="trash-2"></i> Remover
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="text-align:center; padding:24px" class="ax-muted">
                Nenhum anexo encontrado.
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>

        <div style="margin-top:12px">
          {{ $rows->withQueryString()->onEachSide(2)->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();
  });
</script>
@endsection
