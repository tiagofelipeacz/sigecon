{{-- resources/views/admin/config/blank.blade.php --}}
@extends('layouts.sigecon')

@section('title', 'Configurações')

@section('content')
<style>
/* Reaproveita a mesma cara da visão geral p/ header e container */
.gc-container{max-width:1280px;margin:0 auto;padding:16px}
.gc-page-title,
main>h1:first-child,
.content-header h1{font-size:20px;font-weight:700;margin:2px 0 14px;line-height:1.25}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px}
.card-body{padding:14px}
.muted{color:#6b7280}
</style>

<div class="gc-container">
  <h1 class="gc-page-title">Configurações</h1>

  <div class="card">
    <div class="card-body">
      <p class="muted">Escolha um item no menu <b>Configurações</b> para começar.</p>
      {{-- Coloque aqui links/atalhos se desejar, sem mudar funcionalidade --}}
    </div>
  </div>
</div>
@endsection
