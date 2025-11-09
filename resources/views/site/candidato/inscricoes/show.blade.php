@extends('layouts.site')

@section('title', 'Detalhes da inscrição')

@php
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $ins     = $inscricao;
    $concurso= optional($ins->concurso);
    $cargo   = optional($ins->cargo)->nome ?? ($ins->cargo_nome ?? '—');
    $numero  = $ins->numero ?? $ins->numero_inscricao ?? $ins->id;
@endphp

@section('content')
<style>
:root{ --c-primary: {{ $primary }}; --c-border:#e5e7eb; --c-muted:#6b7280; }
.page{ min-height: calc(100vh - 140px); padding: 28px 16px 40px; display:flex; justify-content:center; }
.wrap{ width:100%; max-width:980px; }
.h1{ font-size:22px; font-weight:800; margin:0 0 10px; }
.card{ background:#fff; border:1px solid var(--c-border); border-radius:18px; box-shadow:0 18px 40px rgba(15,23,42,.08); margin-bottom:18px; }
.card-hd{ padding:14px 16px; border-bottom:1px solid var(--c-border); }
.card-tt{ margin:0; font-size:16px; font-weight:800; }
.card-bd{ padding:14px 16px; }
.kv{ display:grid; grid-template-columns: 180px 1fr; gap:6px 14px; font-size:14px; }
.kv dt{ color:#111827; font-weight:700; }
.kv dd{ margin:0; color:#111827; }
.muted{ color:var(--c-muted); }
.badge{ display:inline-flex; padding:3px 8px; border-radius:999px; font-size:12px; border:1px solid var(--c-border); background:#f9fafb; }
.btns{ display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
.btn{ display:inline-flex; padding:7px 12px; border-radius:10px; border:1px solid var(--c-border); text-decoration:none; color:#111827; background:#fff; }
.btn--primary{ border-color:var(--c-primary); }
.list{ margin:0; padding-left:16px; }
</style>

<div class="page">
  <div class="wrap">
    <h1 class="h1">Detalhes da inscrição #{{ $numero }}</h1>

    {{-- Detalhes da inscrição --}}
    <div class="card">
      <div class="card-hd"><h2 class="card-tt">Dados principais</h2></div>
      <div class="card-bd">
        <dl class="kv">
          <dt>Concurso</dt><dd>{{ $concurso->titulo ?? '—' }}</dd>
          <dt>Edital</dt><dd class="muted">{{ $concurso->edital_numero ?? '—' }}</dd>
          <dt>Cargo</dt><dd>{{ $cargo }}</dd>
          <dt>Data da inscrição</dt><dd class="muted">{{ optional($ins->created_at)->format('d/m/Y H:i') }}</dd>
          <dt>Situação</dt><dd><span class="badge">{{ $ins->status ?? $ins->situacao ?? '—' }}</span></dd>
        </dl>

        <div class="btns">
          <a class="btn" href="{{ route('candidato.inscricoes.comprovante', $ins->id) }}" target="_blank">Comprovante</a>
          @if($precisa_enviar_docs)
            <a class="btn btn--primary" href="{{ route('candidato.documentos.create', ['concurso'=>$ins->concurso_id, 'inscricao'=>$ins->id]) }}">
              Enviar documentos
            </a>
          @endif
        </div>
      </div>
    </div>

    {{-- Etapas / Fases --}}
    <div class="card">
      <div class="card-hd"><h2 class="card-tt">Etapas e resultados</h2></div>
      <div class="card-bd">
        @if(method_exists($ins, 'etapas') && $ins->etapas->count())
          <ul class="list">
            @foreach($ins->etapas as $etapa)
              <li>
                <strong>{{ $etapa->nome ?? 'Etapa' }}</strong>
                — {{ optional($etapa->data ?? null)->format('d/m/Y') ?? ($etapa->data ?? '') }}
                @if(!empty($etapa->local_prova)) • Local de prova: {{ $etapa->local_prova }} @endif
                @if(!empty($etapa->nota)) • Nota: {{ $etapa->nota }} @endif
                @if(!empty($etapa->resultado_url))
                  • <a class="btn" href="{{ $etapa->resultado_url }}" target="_blank">Resultado</a>
                @endif
                @if(!empty($etapa->cartao_resposta_url))
                  • <a class="btn" href="{{ $etapa->cartao_resposta_url }}" target="_blank">Espelho do cartão</a>
                @endif
              </li>
            @endforeach
          </ul>
        @else
          <div class="muted">Nenhuma etapa disponível até o momento.</div>
        @endif
      </div>
    </div>

    {{-- Documentos exigidos / enviados --}}
    <div class="card">
      <div class="card-hd"><h2 class="card-tt">Documentos</h2></div>
      <div class="card-bd">
        @if(method_exists($ins, 'documentosRequeridos') && $ins->documentosRequeridos->count())
          <p class="muted">Este concurso exige o envio dos documentos abaixo:</p>
          <ul class="list">
            @foreach($ins->documentosRequeridos as $req)
              <li>
                {{ $req->descricao ?? $req->nome ?? 'Documento' }}
                @if(method_exists($ins, 'documentos'))
                  @php
                    $enviado = $ins->documentos->firstWhere('tipo_id', $req->id) ?? null;
                  @endphp
                  — {!! $enviado ? '<span class="badge">Enviado</span>' : '<span class="badge">Pendente</span>' !!}
                @endif
              </li>
            @endforeach
          </ul>

          <div class="btns">
            <a class="btn btn--primary" href="{{ route('candidato.documentos.create', ['concurso'=>$ins->concurso_id, 'inscricao'=>$ins->id]) }}">
              Enviar/gerenciar documentos
            </a>
          </div>
        @else
          <div class="muted">Não há exigência de documentos para esta inscrição.</div>
        @endif
      </div>
    </div>

  </div>
</div>
@endsection
