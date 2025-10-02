@php
  $layout = collect(['admin.layouts.app','layouts.admin','layouts.app'])
      ->first(fn($v)=>\Illuminate\Support\Facades\View::exists($v)) ?? 'layouts.app';
  $hasVisao  = \Illuminate\Support\Facades\Route::has('admin.concursos.visao-geral');
  $hrefVisao = $hasVisao
      ? route('admin.concursos.visao-geral', $concurso)
      : url('/admin/concursos/'.$concurso->id.'/visao-geral');
@endphp

@extends($layout)

@section('title', 'Concurso: ' . ($concurso->titulo ?? ('#'.$concurso->id)))

@section('content')
<div class="container-fluid" style="display:flex;gap:24px">
  @include('admin.concursos.partials.sidebar-min', ['concurso' => $concurso])

  <div class="flex-grow-1">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h1 style="font-size:20px;font-weight:700;margin:0">
        Concurso: {{ $concurso->titulo ?? ('#'.$concurso->id) }}
      </h1>
      <a href="{{ $hrefVisao }}" class="btn primary">Ir para Visão Geral</a>
    </div>

    <div class="card">
      <div class="card-body">
        <p>Selecione uma opção no menu ao lado para gerenciar o concurso.</p>
        <p>Atalho rápido: <a href="{{ $hrefVisao }}">Visão Geral do Concurso</a>.</p>

        {{-- Exemplo de informações básicas --}}
        <dl class="row" style="margin-top:10px">
          <dt class="col-sm-3">Cliente</dt>
          <dd class="col-sm-9">{{ $concurso->cliente->nome ?? '—' }}</dd>

          <dt class="col-sm-3">Período de Inscrições</dt>
          <dd class="col-sm-9">
            @php
              $ini = optional($concurso->inscricao_inicio)->format('d/m/Y H:i');
              $fim = optional($concurso->inscricao_fim)->format('d/m/Y H:i');
            @endphp
            {{ $ini ? $ini : '—' }} até {{ $fim ? $fim : '—' }}
          </dd>

          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9">
            @if(property_exists($concurso,'ativo') || isset($concurso->ativo))
              {!! ($concurso->ativo ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>') !!}
            @else
              —
            @endif
          </dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
