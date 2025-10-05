{{-- resources/views/admin/concursos/partials/right-menu.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $c  = $concurso ?? (object)[];
    $id = $c->id ?? null;

    // Nome do cliente (fallback abrangente)
    $clienteNome = $c->cliente_nome
        ?? optional($c->client)->cliente
        ?? optional($c->clientLegacy)->cliente
        ?? optional($c->clientAlt)->cliente
        ?? optional($c->clientPlural)->cliente
        ?? optional($c->client)->razao_social
        ?? optional($c->clientLegacy)->razao_social
        ?? optional($c->clientAlt)->razao_social
        ?? optional($c->clientPlural)->razao_social
        ?? null;

    // Título compacto do topo
    $tituloTopo = $c->titulo ?: ($c->numero_edital ? "Processo Seletivo {$c->numero_edital}" : 'Concurso');

    // Helpers
    $urlBase = url("/admin/concursos/{$id}");
    $r = function(string $name, array $params = []) use ($id, $urlBase) {
        return Route::has($name) ? route($name, $params ?: [$id]) : $urlBase;
    };

    // URL da Visão Geral (rota dedicada)
    $hrefVisao = Route::has('admin.concursos.visao-geral')
        ? route('admin.concursos.visao-geral', [$id])
        : url("/admin/concursos/{$id}/visao-geral");

    // Active key: permite override externo via $menu_active. Se não vier, deduz pela URL.
    $activeKey = $menu_active ?? null;
    if ($activeKey === null) {
        $path   = trim(request()->path(), '/');           // admin/concursos/8/visao-geral
        $prefix = trim("admin/concursos/{$id}", '/');     // admin/concursos/8
        $first  = 'root';
        if ($id && $path !== $prefix) {
            $rest  = Str::after($path, $prefix . '/');    // visao-geral/...
            $first = Str::before($rest, '/');             // visao-geral
        }
        // Mapa para evitar colisão: "visao-geral" => "root"
        $map = [
            '' => 'root',
            'visao-geral' => 'root',
            'visao_geral' => 'root',
        ];
        $activeKey = $map[$first] ?? $first ?? 'root';
    }

    // ===== Itens (ordem e rótulos conforme solicitado) =====
    $items = [
        ['key'=>'root',            'label'=>'Visão Geral',              'icon'=>'bar-chart-3',    'url'=> $hrefVisao],
        ['key'=>'config',          'label'=>'Configurações',            'icon'=>'settings',       'url'=> "{$urlBase}/config"],
        ['key'=>'cronograma',      'label'=>'Cronograma',               'icon'=>'calendar',       'url'=> "{$urlBase}/cronograma"],

        // Novos rótulos mantendo as rotas existentes
        ['key'=>'impugnacoes',     'label'=>'Impugnações do Edital',    'icon'=>'file-warning',   'url'=> "{$urlBase}/impugnacoes"],
        ['key'=>'divulgacoes',     'label'=>'Respostas e Resultados',   'icon'=>'megaphone',      'url'=> "{$urlBase}/divulgacoes"],

        ['key'=>'anexos',          'label'=>'Anexos',                   'icon'=>'paperclip',      'url'=> "{$urlBase}/anexos"],
        ['key'=>'vagas',           'label'=>'Vagas',                    'icon'=>'briefcase',      'url'=> "{$urlBase}/vagas"],
        ['key'=>'cidades',         'label'=>'Cidade de Provas',         'icon'=>'map-pin',        'url'=> "{$urlBase}/cidades"],
        ['key'=>'inscricoes',      'label'=>'Inscrições',               'icon'=>'user-plus',      'url'=> "{$urlBase}/inscricoes"],
        ['key'=>'documentos',      'label'=>'Documentos',               'icon'=>'file-text',      'url'=> "{$urlBase}/documentos"],

        // Renomeado (rota permanece /divergencias)
        ['key'=>'divergencias',    'label'=>'Alterações de Cadastro',   'icon'=>'alert-triangle', 'url'=> "{$urlBase}/divergencias"],

        ['key'=>'devolucao',       'label'=>'Devolução de Taxa',        'icon'=>'rotate-ccw',     'url'=> "{$urlBase}/devolucao"],
        ['key'=>'subjudice',       'label'=>'Sub Judice',               'icon'=>'scale',          'url'=> "{$urlBase}/subjudice"],
        ['key'=>'etapas',          'label'=>'Etapas',                   'icon'=>'layers',         'url'=> "{$urlBase}/etapas"],
        ['key'=>'resultado-final', 'label'=>'Resultado Final',          'icon'=>'trophy',         'url'=> "{$urlBase}/resultado-final"],

        ['key'=>'relatorios',      'label'=>'Relatórios',               'icon'=>'line-chart',     'url'=> "{$urlBase}/relatorios"],
    ];
@endphp

<style>
  .rmenu{ width:250px; position:sticky; top:80px; align-self:flex-start; }
  .rmenu .box{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
  .rmenu .head{ padding:12px 14px; border-bottom:1px solid #e5e7eb; }
  .rmenu .title{ font-weight:700; line-height:1.2; }
  .rmenu .sub{ color:#64748b; font-size:12px; }

  .rmenu .list{ padding:6px; }
  .rmenu .mi{
    display:flex; align-items:center; gap:10px;
    padding:9px 10px; border-radius:8px; color:#0f172a; text-decoration:none; font-weight:500;
  }
  .rmenu .mi:hover{ background:#f3f4f6; }
  .rmenu .mi.active{ background:#eef2ff; color:#1e3a8a; }
  .rmenu .mi i{ width:18px; height:18px; }
</style>

<aside class="rmenu">
  <div class="box">
    <div class="head">
      <div class="title">{{ $tituloTopo }}</div>
    </div>

    <nav class="list" aria-label="Seções do concurso">
      @foreach ($items as $it)
        <a href="{{ $it['url'] }}" class="mi {{ ($activeKey === $it['key']) ? 'active' : '' }}">
          <i data-lucide="{{ $it['icon'] }}"></i>
          <span>{{ $it['label'] }}</span>
        </a>
      @endforeach
    </nav>
  </div>
</aside>

@once
  {{-- Lucide Icons --}}
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endonce
