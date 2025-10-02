@section('content')
{{-- resources/views/admin/concursos/partials/sidebar-min.blade.php --}}
@php
    /** @var mixed $concurso */
    // Garante que sempre teremos o ID (int) do concurso
    $id = null;
    if (is_object($concurso ?? null))     { $id = $concurso->id ?? null; }
    elseif (is_array($concurso ?? null))  { $id = $concurso['id'] ?? null; }
    elseif (is_numeric($concurso ?? null)){ $id = (int) $concurso; }

    // Evita erro se não existir o relacionamento etapas()
    $temEtapas = (isset($concurso) && is_object($concurso) && method_exists($concurso, 'etapas'));
    $etapas = $temEtapas ? $concurso->etapas()->orderBy('created_at')->get() : collect();

    // Helper para marcar link ativo (rota ou path)
    $isActive = function ($patterns) {
        foreach ((array) $patterns as $p) {
            if (request()->routeIs($p) || request()->is($p)) {
                return 'gc-active';
            }
        }
        return '';
    };

    // ====== Links com fallback (sempre funcionam) ======
    $hasVisao  = \Illuminate\Support\Facades\Route::has('admin.concursos.visao-geral');
    $hrefVisao = $hasVisao
        ? route('admin.concursos.visao-geral', ['concurso' => $id])
        : ($id ? url("/admin/concursos/$id/visao-geral") : '#');

    $hasImp = \Illuminate\Support\Facades\Route::has('admin.concursos.impugnacoes.index');
    $hrefImp = $hasImp
        ? route('admin.concursos.impugnacoes.index', ['concurso' => $id])
        : ($id ? url("/admin/concursos/$id/impugnacoes") : '#');

    $hasVagas  = \Illuminate\Support\Facades\Route::has('admin.concursos.vagas.index');
    $hrefVagas = $hasVagas
        ? route('admin.concursos.vagas.index', ['concurso' => $id])
        : ($id ? url("/admin/concursos/$id/vagas") : '#');
@endphp

<style>
:root{ --gc-sidebar-w:240px; --gc-gap:16px; }

/* Grade padrão usada na visão geral */
.gc-wrap{display:flex;align-items:flex-start;gap:var(--gc-gap);}
.gc-sidebar{position:sticky;top:84px;width:var(--gc-sidebar-w);flex:0 0 var(--gc-sidebar-w);}
.gc-main{flex:1;min-width:0}

/* Cartões, listas e títulos com padrão único */
.gc-sidebar .gc-box{border:1px solid #e5e7eb;border-radius:10px;overflow:visible;background:#fff}
.gc-sidebar h3{margin:0;padding:10px 12px;font-size:13px;font-weight:700;background:#f9fafb;border-bottom:1px solid #eef0f3}
.gc-sidebar ul{list-style:none;margin:0;padding:6px}
.gc-sidebar a{display:block;padding:8px 10px;border-radius:8px;text-decoration:none;color:#111827}
.gc-sidebar a:hover{background:#f3f4f6}
.gc-sidebar .muted{color:#6b7280;font-size:12px;padding:8px 10px}
.gc-sidebar .group-title{padding:6px 10px;color:#6b7280;font-size:11px;text-transform:uppercase}
.gc-sidebar a.gc-active{background:#ecfdf5;color:#065f46;font-weight:600}
.gc-sub{margin:4px 0 8px 10px;padding-left:8px;border-left:2px solid #eef2f7}
.gc-sub a{padding:6px 8px;font-size:13px}
.gc-disabled{opacity:.5;pointer-events:none}

/* Flyout (submenu) */
.gc-has-fly{position:relative}
.gc-fly{position:absolute;left:100%;top:0;width:280px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.08);opacity:0;visibility:hidden;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease,visibility 0s linear .15s;z-index:1000}
.gc-has-fly:hover>.gc-fly{opacity:1;visibility:visible;transform:translateY(0);transition-delay:0s}
.gc-fly h4{margin:0;padding:10px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #eef0f3}
.gc-fly .fly-list{padding:6px}
.gc-fly .fly-list a{display:block;padding:8px 10px;border-radius:8px;color:#111827;text-decoration:none}
.gc-fly .fly-list a:hover{background:#f3f4f6}

/* Padroniza o título das telas (primeiro h1) do lado direito */
.gc-page-title,
.gc-main>h1:first-child,
main>h1:first-child,
.content-header h1{font-size:20px;font-weight:700;margin:2px 0 14px;line-height:1.25}
</style>

<aside class="gc-sidebar">
  <div class="gc-box">
    <h3>Menu do concurso</h3>

    <ul>
      @if($id)
        <li><a href="{{ $hrefVisao }}" class="{{ $isActive(['admin.concursos.visao-geral','admin/concursos/*/visao-geral']) }}">Visão Geral</a></li>

        <li>
          <a href="{{ route('admin.concursos.config', ['concurso' => $id]) }}"
             class="{{ $isActive(['admin.concursos.config','admin/concursos/*/config']) }}">
            Configurações
          </a>
        </li>

        <li><a href="{{ url('/admin/concursos/'.$id.'/divulgacoes') }}">Divulgações</a></li>

        {{-- Impugnações --}}
        <li>
          <a href="{{ $hrefImp }}"
             class="{{ $isActive(['admin.concursos.impugnacoes.*','admin/concursos/*/impugnacoes*']) }}">
            Impugnações
          </a>
        </li>

        <li><a href="{{ url('/admin/concursos/'.$id.'/anexos') }}">Anexos</a></li>

        {{-- Vagas --}}
        <li>
          <a href="{{ $hrefVagas }}"
             class="{{ $isActive(['admin.concursos.vagas.*','admin/concursos/*/vagas*']) }}">
            Vagas
          </a>
        </li>

        {{-- Inscrições + subitens --}}
        <li class="gc-has-fly">
          <a href="{{ url('/admin/concursos/'.$id.'/inscricoes') }}"
             class="{{ $isActive([
               'admin/concursos/*/inscricoes*','admin.concursos.inscricoes.*',
               'admin/concursos/*/inscritos*','admin.concursos.inscritos.*',
               'admin/concursos/*/laudos*','admin.concursos.laudos.*',
               'admin/concursos/*/vagas-reservadas*','admin.concursos.vagas-reservadas.*',
               'admin/concursos/*/isencoes*','admin.concursos.isencoes.*',
               'admin/concursos/*/sistac*','admin.concursos.sistac.*',
               'admin/concursos/*/condicoes-especiais*','admin.concursos.condicoes-especiais.*',
               'admin/concursos/*/mesarios*','admin.concursos.mesarios.*',
               'admin/concursos/*/jurados*','admin.concursos.jurados.*',
               'admin/concursos/*/nome-social*','admin.concursos.nome-social.*',
             ]) }}">
            Inscrições
          </a>

          @php
            $menuInscricoes = [
              ['label'=>'Inscritos',    'route'=>'admin.concursos.inscritos.index',     'path'=>"/admin/concursos/$id/inscritos",        'active'=>['admin.concursos.inscritos.*','admin/concursos/*/inscritos*']],
              ['label'=>'Laudo Médico', 'route'=>'admin.concursos.laudos.index',        'path'=>"/admin/concursos/$id/laudos-medicos",   'active'=>['admin.concursos.laudos.*','admin/concursos/*/laudos*']],
            ];
            $menuProtocolos = [
              ['label'=>'Vagas Reservadas',    'route'=>'admin.concursos.vagas-reservadas.index', 'path'=>"/admin/concursos/$id/vagas-reservadas",    'active'=>['admin.concursos.vagas-reservadas.*','admin/concursos/*/vagas-reservadas*']],
              ['label'=>'Pedidos de Isenção',  'route'=>'admin.concursos.isencoes.index',          'path'=>"/admin/concursos/$id/isencoes",            'active'=>['admin.concursos.isencoes.*','admin/concursos/*/isencoes*']],
              ['label'=>'Integração SISTAC',   'route'=>'admin.concursos.sistac.index',            'path'=>"/admin/concursos/$id/sistac",               'active'=>['admin.concursos.sistac.*','admin/concursos/*/sistac*']],
              ['label'=>'Condições Especiais', 'route'=>'admin.concursos.condicoes-especiais.index','path'=>"/admin/concursos/$id/condicoes-especiais",  'active'=>['admin.concursos.condicoes-especiais.*','admin/concursos/*/condicoes-especiais*']],
              ['label'=>'Mesários',            'route'=>'admin.concursos.mesarios.index',          'path'=>"/admin/concursos/$id/mesarios",             'active'=>['admin.concursos.mesarios.*','admin/concursos/*/mesarios*']],
              ['label'=>'Jurados',             'route'=>'admin.concursos.jurados.index',           'path'=>"/admin/concursos/$id/jurados",              'active'=>['admin.concursos.jurados.*','admin/concursos/*/jurados*']],
              ['label'=>'Nome Social',         'route'=>'admin.concursos.nome-social.index',       'path'=>"/admin/concursos/$id/nome-social",          'active'=>['admin.concursos.nome-social.*','admin/concursos/*/nome-social*']],
            ];
          @endphp

          <div class="gc-fly" role="menu" aria-label="Inscrições">
            <h4>Inscrições</h4>
            <div class="fly-list">
              @foreach ($menuInscricoes as $it)
                @php
                  $has = \Illuminate\Support\Facades\Route::has($it['route']);
                  $href = $has ? route($it['route'], ['concurso' => $id]) : url($it['path']);
                  $disabled = $has ? '' : 'gc-disabled';
                @endphp
                <a href="{{ $href }}" class="{{ $disabled }} {{ $isActive($it['active']) }}">{{ $it['label'] }}</a>
              @endforeach
            </div>

            <h4>Protocolos</h4>
            <div class="fly-list">
              @foreach ($menuProtocolos as $it)
                @php
                  $has = \Illuminate\Support\Facades\Route::has($it['route']);
                  $href = $has ? route($it['route'], ['concurso' => $id]) : url($it['path']);
                  $disabled = $has ? '' : 'gc-disabled';
                @endphp
                <a href="{{ $href }}" class="{{ $disabled }} {{ $isActive($it['active']) }}">{{ $it['label'] }}</a>
              @endforeach
            </div>
          </div>
        </li>

        <li><a href="{{ url('/admin/concursos/'.$id.'/documentos') }}">Documentos</a></li>
        <li><a href="{{ url('/admin/concursos/'.$id.'/resultado-final') }}">Resultado Final</a></li>
        <li><a href="{{ url('/admin/concursos/'.$id.'/ferramentas') }}">Ferramentas</a></li>
        <li><a href="{{ url('/admin/concursos/'.$id.'/relatorios') }}">Relatórios</a></li>
      @endif
    </ul>

    {{-- Etapas dinâmicas --}}
    <div>
      <div class="group-title">Etapas criadas</div>
      @if($etapas->count())
        <ul>
          @foreach($etapas as $e)
            @php $titulo = strtoupper($e->titulo ?? $e->tipo ?? ('Etapa #'.$e->id)); @endphp
            <li>
              <a href="{{ url('/admin/concursos/'.$id.'/etapas/'.$e->id) }}">Etapa: <b>{{ $titulo }}</b></a>
            </li>
          @endforeach
        </ul>
      @else
        <div class="muted">Nenhuma etapa criada ainda. Use “Etapas” para adicionar.</div>
      @endif
    </div>
  </div>
</aside>

@endsection
