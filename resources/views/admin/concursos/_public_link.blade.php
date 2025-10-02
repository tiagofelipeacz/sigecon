@section('content')
﻿@php
    $url = \Route::has('site.concursos.show') ? route('site.concursos.show', $concurso->id) : url('/concursos/'.$concurso->id);
@endphp
<a href="{{ $url }}" class="btn btn-sm btn-outline-success ms-1" target="_blank" rel="noopener">Ver publico</a>

@endsection
