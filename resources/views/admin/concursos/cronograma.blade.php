{{-- resources/views/admin/concursos/cronograma.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Cronograma - SIGECON')

@php
  // Garantias
  $itens = $itens ?? [];
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-row-2  { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
  .table { width:100%; border-collapse: collapse; }
  .table thead th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table tbody td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; }
  .table-sm thead th, .table-sm tbody td{ padding:6px 8px; font-size:13px; }
  .text-muted{ color:#6b7280; }
  .mb-2{ margin-bottom:8px; }
  .mb-3{ margin-bottom:12px; }
  .mb-4{ margin-bottom:16px; }
  .btn { display:inline-flex; align-items:center; gap:8px; border:1px solid #e5e7eb; background:#f9fafb; padding:6px 10px; border-radius:8px; cursor:pointer; text-decoration:none; color:#111827;}
  .btn:hover{ background:#f3f4f6; }
  .btn.primary{ background:#1f2937; color:#fff; border-color:#1f2937; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }
  .btn.link{ background:transparent; border:none; color:#1d4ed8; padding:0; }
  .input, .textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
  .label{ font-size:12px; color:#6b7280; margin-bottom:4px; display:block; }
  .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:10px; }
  .grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
  .switch{ display:flex; align-items:center; gap:8px; }
</style>

<div class="gc-page">
  {{-- Lateral: menu padrão --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => $menu_active ?? 'cronograma'
    ])
  </div>

  {{-- Conteúdo principal --}}
  <div>

    {{-- Título simples (padrão “config”: sem caixas/bordas enfeitadas) --}}
    <div class="mb-3" style="font-weight:700; font-size:18px;">Cronograma</div>

    <div class="gc-row-2">
      {{-- Formulário: novo item --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">Adicionar Item</div>
          <form method="POST" action="{{ route('admin.concursos.cronograma.store', $concurso) }}">
            @csrf
            <div class="mb-2">
              <label class="label">Título *</label>
              <input type="text" name="titulo" class="input" required>
            </div>

            <div class="mb-2 grid-3">
              <div>
                <label class="label">Início (ex: 20/10/2025 08:00)</label>
                <input type="text" name="inicio" class="input" placeholder="dd/mm/aaaa hh:mm">
              </div>
              <div>
                <label class="label">Fim (opcional)</label>
                <input type="text" name="fim" class="input" placeholder="dd/mm/aaaa hh:mm">
              </div>
              <div>
                <label class="label">Local (opcional)</label>
                <input type="text" name="local" class="input" placeholder="Auditório...">
              </div>
            </div>

            <div class="mb-2">
              <label class="label">Descrição (opcional)</label>
              <textarea name="descricao" class="textarea" rows="3"></textarea>
            </div>

            <div class="mb-3 switch">
              <input type="checkbox" name="publicar" id="publicar_novo" value="1">
              <label for="publicar_novo">Publicar no site</label>
            </div>

            <button class="btn primary" type="submit">
              <i data-lucide="plus"></i>
              Salvar item
            </button>
          </form>
        </div>
      </div>

      {{-- Reordenar itens --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">Reordenar</div>
          <form method="POST" action="{{ route('admin.concursos.cronograma.reorder', $concurso) }}">
            @csrf
            @foreach ($itens as $it)
              <div class="grid-2 mb-2" style="align-items:center;">
                <div class="text-muted">
                  #{{ $it->id }} — {{ $it->titulo }}
                </div>
                <div style="justify-self:end; display:flex; align-items:center; gap:6px;">
                  <label class="label" style="margin:0;">Ordem</label>
                  <input type="number" class="input" name="ordem[{{ $it->id }}]" value="{{ $it->ordem }}" min="1" style="width:90px;">
                </div>
              </div>
            @endforeach

            <button class="btn primary" type="submit">
              <i data-lucide="check"></i>
              Salvar ordem
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- Lista de itens --}}
    <div class="gc-card" style="margin-top:14px;">
      <div class="gc-body">
        <div class="mb-2" style="font-weight:600">Itens do Cronograma</div>

        <table class="table table-sm">
          <thead>
            <tr>
              <th style="width:70px;">Ordem</th>
              <th>Título / Período</th>
              <th style="width:20%;">Local</th>
              <th style="width:120px;">Publicado?</th>
              <th style="width:220px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($itens as $it)
              @php
                $ini = $it->inicio ? \Carbon\Carbon::parse($it->inicio)->format('d/m/Y H:i') : '—';
                $fim = $it->fim    ? \Carbon\Carbon::parse($it->fim)->format('d/m/Y H:i')    : null;
                $periodo = $fim ? "{$ini} → {$fim}" : $ini;
              @endphp
              <tr>
                <td>{{ $it->ordem }}</td>
                <td>
                  <div style="font-weight:600;">{{ $it->titulo }}</div>
                  <div class="text-muted" style="font-size:12px;">{{ $periodo }}</div>
                  @if ($it->descricao)
                    <div class="text-muted" style="font-size:12px; margin-top:4px;">{!! nl2br(e($it->descricao)) !!}</div>
                  @endif
                </td>
                <td>{{ $it->local ?: '—' }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.concursos.cronograma.toggle', [$concurso, $it]) }}">
                    @csrf
                    <button class="btn" type="submit" title="Alternar publicação">
                      @if ($it->publicar)
                        <i data-lucide="eye"></i> Publicado
                      @else
                        <i data-lucide="eye-off"></i> Oculto
                      @endif
                    </button>
                  </form>
                </td>
                <td>
                  {{-- Editar (inline simples) --}}
                  <details>
                    <summary class="btn link"><i data-lucide="edit-3"></i> Editar</summary>
                    <div style="margin-top:8px;">
                      <form method="POST" action="{{ route('admin.concursos.cronograma.update', [$concurso, $it]) }}">
                        @csrf @method('PUT')
                        <div class="mb-2">
                          <label class="label">Título *</label>
                          <input type="text" name="titulo" class="input" value="{{ $it->titulo }}" required>
                        </div>
                        <div class="grid-3 mb-2">
                          <div>
                            <label class="label">Início</label>
                            <input type="text" name="inicio" class="input" value="{{ $it->inicio ? \Carbon\Carbon::parse($it->inicio)->format('d/m/Y H:i') : '' }}">
                          </div>
                          <div>
                            <label class="label">Fim</label>
                            <input type="text" name="fim" class="input" value="{{ $it->fim ? \Carbon\Carbon::parse($it->fim)->format('d/m/Y H:i') : '' }}">
                          </div>
                          <div>
                            <label class="label">Local</label>
                            <input type="text" name="local" class="input" value="{{ $it->local }}">
                          </div>
                        </div>
                        <div class="mb-2">
                          <label class="label">Descrição</label>
                          <textarea name="descricao" class="textarea" rows="3">{{ $it->descricao }}</textarea>
                        </div>
                        <div class="mb-3 switch">
                          <input type="checkbox" name="publicar" id="pub_{{ $it->id }}" value="1" {{ $it->publicar ? 'checked' : '' }}>
                          <label for="pub_{{ $it->id }}">Publicar no site</label>
                        </div>
                        <div class="grid-2" style="align-items:center;">
                          <div>
                            <label class="label">Ordem</label>
                            <input type="number" name="ordem" class="input" value="{{ $it->ordem }}" min="1" style="width:110px;">
                          </div>
                          <div style="justify-self:end;">
                            <button class="btn primary" type="submit">
                              <i data-lucide="save"></i> Salvar
                            </button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </details>

                  {{-- Excluir --}}
                  <form method="POST" action="{{ route('admin.concursos.cronograma.destroy', [$concurso, $it]) }}" onsubmit="return confirm('Remover este item?');" style="display:inline-block; margin-left:8px;">
                    @csrf @method('DELETE')
                    <button class="btn danger" type="submit">
                      <i data-lucide="trash-2"></i> Excluir
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted">Nenhum item cadastrado.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endonce
@endsection
