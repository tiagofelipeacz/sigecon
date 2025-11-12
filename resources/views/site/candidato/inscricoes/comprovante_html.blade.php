{{-- resources/views/site/candidato/inscricoes/comprovante_html.blade.php --}}
@extends('layouts.site')

@section('title', 'Comprovante de inscrição')

@section('content')
    <div style="max-width:900px;margin:24px auto;">
        <a href="#" onclick="window.print(); return false;"
           style="display:inline-block;margin-bottom:12px;padding:6px 12px;border-radius:999px;border:1px solid #e5e7eb;">
            Imprimir
        </a>

        @include('site.candidato.inscricoes.comprovante_pdf')
    </div>
@endsection
