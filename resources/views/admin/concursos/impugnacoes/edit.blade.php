@extends('layouts.sigecon')
@section('title', 'Impugnação do Edital - SIGECON')

@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Carbon;

  // Espera-se: $concurso, $impugnacao
  $i = $impugnacao ?? (object)[];
  $situacao = strtolower((string)($i->situacao ?? $i->status ?? 'pendente'));
  $respText = old('resposta', $i->resposta ?? $i->texto_resposta ?? '');
  $respData = old('data_resposta', optional($i->respondido_em ?? $i->data_resposta ?? null)->format('Y-m-d\TH:i'));
  $argTexto = $i->argumento ?? $i->texto ?? $i->mensagem ?? '';
  $nome     = $i->nome ?? $i->candidato_nome ?? $i->requerente ?? '—';
@endphp

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }

  .sec .hd{ padding:10px 12px; border-bottom:1px solid #e5e7eb; font-weight:700; }
  .sec .ct{ padding:12px; }
  .row{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
  .row-3{ display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; }
  .muted{ color:#6b7280; font-size:12px; }

  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:8px 11px; font-size:13px; }
  .textarea{ width:100%; min-height:160px; border:1px solid #e5e7eb; border-radius:10px; padding:10px; font-size:14px; line-height:1.5; resize:vertical; }

  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; background:#fff; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }

  .kv{ display:grid; grid-template-columns: 180px 1fr; gap:10px; padding:6px 0; }
  .kv .k{ color:#6b7280; font-size:12px; }
  .kv .v{ font-weight:600; }

  .chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:2px 10px; border:1px solid #e5e7eb; background:#fff; }
  .chip.pendente{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
  .chip.deferido{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
  .chip.indeferido{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'impugnacoes'
    ])
  </div>

  <div class="grid" style="gap:14px">

    <div class="gc-card sec">
      <div class="hd">Opções Gerais</div>
      <div class="ct">
        <div class="row">
          <div class="kv">
            <div class="k">ID:</div>
            <div class="v">{{ $i->id }}</div>

            <div class="k">Candidato/Requerente:</div>
            <div class="v">
              {{ $nome }}
              @if(($i->cpf ?? '') !== '')
                <div class="muted">CPF: {{ $i->cpf }}</div>
              @endif
            </div>

            @if(($i->telefone ?? '') || ($i->telefone2 ?? ''))
              <div class="k">Telefone(s):</div>
              <div class="v">
                {{ trim(($i->telefone ?? '').' '.($i->telefone2 ?? '')) }}
              </div>
            @endif

            @if(($i->email ?? '') !== '')
              <div class="k">E-mail:</div>
              <div class="v">{{ $i->email }}</div>
            @endif

            @if(($i->endereco ?? '') !== '')
              <div class="k">Endereço:</div>
              <div class="v">{{ $i->endereco }}</div>
            @endif

            <div class="k">Data Envio:</div>
            <div class="v">
              {{ optional($i->created_at ?? $i->data_envio ?? null)->format('d/m/Y H:i') ?? '—' }}
            </div>
          </div>

          <div>
            <div class="k">Situação atual</div>
            <div style="margin-top:6px">
              <span class="chip {{ in_array($situacao,['deferido','indeferido','pendente']) ? $situacao : 'pendente' }}">
                {{ ucfirst($situacao) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="gc-card sec">
      <div class="hd">Argumentos</div>
      <div class="ct">
        <div class="textarea" style="min-height:220px; background:#f9fafb;" readonly>{{ $argTexto }}</div>
      </div>
    </div>

    <div class="gc-card sec">
      <div class="hd">Resposta</div>
      <div class="ct">
        <form method="post" action="{{ route('admin.concursos.impugnacoes.update', [$concurso, $i->id]) }}" class="grid" style="gap:12px">
          @csrf
          @method('PUT')

          <div class="row">
            <div>
              <label class="muted">Situação</label>
              <select name="situacao" class="input" required>
                <option value="pendente"   @selected($situacao==='pendente')>Pendente</option>
                <option value="deferido"   @selected($situacao==='deferido')>Deferido</option>
                <option value="indeferido" @selected($situacao==='indeferido')>Indeferido</option>
              </select>
            </div>
            <div>
              <label class="muted">Data Resposta</label>
              <input type="datetime-local" name="data_resposta" class="input"
                     value="{{ $respData ?? now()->format('Y-m-d\TH:i') }}">
            </div>
          </div>

          <div>
            <label class="muted">Resposta:</label>
            <textarea name="resposta" class="textarea" rows="10" placeholder="Escreva aqui a resposta ao requerente...">{{ $respText }}</textarea>
          </div>

          <div style="display:flex; gap:8px; flex-wrap:wrap">
            <button class="btn primary" type="submit"><i data-lucide="save"></i> Salvar e Fechar</button>
            <a class="btn" href="{{ route('admin.concursos.impugnacoes.index', $concurso) }}"><i data-lucide="list"></i> Cancelar</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons())</script>
@endsection
