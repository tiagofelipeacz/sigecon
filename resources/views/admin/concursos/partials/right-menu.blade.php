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

    // Título compacto
    $tituloTopo = $c->titulo ?: ($c->numero_edital ? "Processo Seletivo {$c->numero_edital}" : 'Concurso');

    // Helpers
    $urlBase = url("/admin/concursos/{$id}");
    $r = function (string $name, array $params = []) use ($id, $urlBase) {
        return Route::has($name) ? route($name, $params ?: [$id]) : $urlBase;
    };

    // tenta a primeira rota disponível; senão, cai para /admin/concursos/{id}/{segmento}
    $to = function (array $names, string $fallbackSegment) use ($id, $urlBase) {
        foreach ($names as $n) {
            if (Route::has($n)) return route($n, [$id]);
        }
        return "{$urlBase}/{$fallbackSegment}";
    };

    // Visão Geral
    $hrefVisao = Route::has('admin.concursos.visao-geral')
        ? route('admin.concursos.visao-geral', [$id])
        : url("/admin/concursos/{$id}/visao-geral");

    // Active key (permite override via $menu_active)
    $activeKey = $menu_active ?? null;
    if ($activeKey === null) {
        $path   = trim(request()->path(), '/');           // admin/concursos/8/visao-geral
        $prefix = trim("admin/concursos/{$id}", '/');     // admin/concursos/8
        $first  = 'root';
        if ($id && $path !== $prefix) {
            $rest  = Str::after($path, $prefix . '/');    // visao-geral/...
            $first = Str::before($rest, '/');             // visao-geral
        }
        $map = [
            '' => 'root',
            'visao-geral' => 'root',
            'visao_geral' => 'root',
        ];
        $activeKey = $map[$first] ?? $first ?? 'root';
    }

    // ===== Itens principais =====
    $items = [
        ['key'=>'root',            'label'=>'Visão Geral',              'icon'=>'bar-chart-3',    'url'=> $hrefVisao],
        ['key'=>'config',          'label'=>'Configurações',            'icon'=>'settings',       'url'=> "{$urlBase}/config"],
        ['key'=>'cronograma',      'label'=>'Cronograma',               'icon'=>'calendar',       'url'=> "{$urlBase}/cronograma"],

        ['key'=>'impugnacoes',     'label'=>'Impugnações do Edital',    'icon'=>'file-warning',   'url'=> "{$urlBase}/impugnacoes"],
        ['key'=>'divulgacoes',     'label'=>'Respostas e Resultados',   'icon'=>'megaphone',      'url'=> "{$urlBase}/divulgacoes"],

        ['key'=>'anexos',          'label'=>'Anexos',                   'icon'=>'paperclip',      'url'=> "{$urlBase}/anexos"],
        ['key'=>'vagas',           'label'=>'Vagas',                    'icon'=>'briefcase',      'url'=> "{$urlBase}/vagas"],
        ['key'=>'cidades',         'label'=>'Cidade de Provas',         'icon'=>'map-pin',        'url'=> "{$urlBase}/cidades"],

        // O item "inscricoes" ganha flyout
        ['key'=>'inscricoes',      'label'=>'Inscrições',               'icon'=>'user-plus',      'url'=> "{$urlBase}/inscricoes"],

        ['key'=>'documentos',      'label'=>'Documentos',               'icon'=>'file-text',      'url'=> "{$urlBase}/documentos"],
        ['key'=>'divergencias',    'label'=>'Alterações de Cadastro',   'icon'=>'alert-triangle', 'url'=> "{$urlBase}/divergencias"],
        ['key'=>'devolucao',       'label'=>'Devolução de Taxa',        'icon'=>'rotate-ccw',     'url'=> "{$urlBase}/devolucao"],
        ['key'=>'subjudice',       'label'=>'Sub Judice',               'icon'=>'scale',          'url'=> "{$urlBase}/subjudice"],
        ['key'=>'etapas',          'label'=>'Etapas',                   'icon'=>'layers',         'url'=> "{$urlBase}/etapas"],
        ['key'=>'resultado-final', 'label'=>'Resultado Final',          'icon'=>'trophy',         'url'=> "{$urlBase}/resultado-final"],

        ['key'=>'relatorios',      'label'=>'Relatórios',               'icon'=>'line-chart',     'url'=> "{$urlBase}/relatorios"],
    ];

    // ===== Itens do flyout "Inscrições" =====
    $fly = [
        ['sep'=>true, 'label'=>'INSCRIÇÕES'],
        ['key'=>'inscritos',         'label'=>'Inscritos',
         'url'=> $to(['admin.concursos.inscritos.index'], 'inscritos')],
        ['key'=>'laudo-medico|laudomedico', 'label'=>'Laudo Médico',
         'url'=> $to(['admin.concursos.laudomedico.index'], 'laudo-medico')],

        ['sep'=>true, 'label'=>'AÇÕES'],
        ['key'=>'nova-inscricao|novainscricao|inscricoes-nova', 'label'=>'Nova Inscrição',
         'url'=> $to(['admin.concursos.inscricoes.create','admin.concursos.inscricoes.nova'], 'inscricoes/nova')],
        ['key'=>'importar-inscricoes|importarinscricoes|inscricoes-importar', 'label'=>'Importar Inscrições',
         'url'=> $to(['admin.concursos.inscricoes.importar','admin.concursos.importar-inscricoes'], 'inscricoes/importar')],
        ['key'=>'dados-extras|dadosextras', 'label'=>'Dados Extras',
         'url'=> $to(['admin.concursos.dadosextras.index'], 'dados-extras')],

        ['sep'=>true, 'label'=>'PROTOCOLOS'],
        ['key'=>'isencoes|pedidos-isencao', 'label'=>'Pedidos de Isenção',
         'url'=> $to(['admin.concursos.isencoes.index'], 'isencoes')],
        ['key'=>'sistac|integracao-sistac', 'label'=>'Integração SISTAC',
         'url'=> $to(['admin.concursos.sistac.index'], 'sistac')],
        ['key'=>'condicoes-especiais|condicoesespeciais', 'label'=>'Condições Especiais',
         'url'=> $to(['admin.concursos.condicoesespeciais.index'], 'condicoes-especiais')],
        ['key'=>'vagas-reservadas|reservadas|vagasreservadas', 'label'=>'Vagas Reservadas',
         'url'=> $to(['admin.concursos.vagasreservadas.index'], 'vagas-reservadas')],
        ['key'=>'mesarios',           'label'=>'Mesários',
         'url'=> $to(['admin.concursos.mesarios.index'], 'mesarios')],
        ['key'=>'jurados',            'label'=>'Jurados',
         'url'=> $to(['admin.concursos.jurados.index'], 'jurados')],
        ['key'=>'nome-social|nomesocial', 'label'=>'Nome Social',
         'url'=> $to(['admin.concursos.nomesocial.index'], 'nome-social')],
    ];

    // Considera o grupo "Inscrições" ativo se algum filho estiver ativo (sem whereNot)
    $flyKeysFlat = collect($fly)
        ->filter(fn($op) => empty($op['sep']))
        ->pluck('key')
        ->flatMap(fn($k) => is_string($k) ? explode('|', $k) : [])
        ->values()
        ->all();

    $isGroupInscricoesActive = in_array($activeKey, $flyKeysFlat, true) || $activeKey === 'inscricoes';
@endphp

<style>
  .rmenu{ width:250px; position:sticky; top:80px; align-self:flex-start; overflow:visible; }
  .rmenu .box{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:visible; }
  .rmenu .head{ padding:12px 14px; border-bottom:1px solid #e5e7eb; }
  .rmenu .title{ font-weight:700; line-height:1.2; }
  .rmenu .sub{ color:#64748b; font-size:12px; }

  .rmenu .list{ padding:6px; position:relative; overflow:visible; }
  .rmenu .mi{
    display:flex; align-items:center; gap:10px;
    padding:9px 10px; border-radius:8px; color:#0f172a; text-decoration:none; font-weight:500;
    position:relative;
  }
  .rmenu .mi:hover{ background:#f3f4f6; }
  .rmenu .mi.active{ background:#eef2ff; color:#1e3a8a; }
  .rmenu .mi i{ width:18px; height:18px; }

  /* Flyout */
  .fly-wrap{ position:relative; }
  .fly-wrap .mi.has-caret::after{
    content:''; width:6px; height:6px; border-right:2px solid currentColor; border-top:2px solid currentColor;
    transform: rotate(45deg); margin-left:auto; opacity:.5;
  }
  .flyout{
    display:none; position:absolute; left:100%; top:0;
    /* Sem gap entre o item e o painel (evita "piscada") */
    margin-left:0; z-index:50;
    width:320px; background:#fff; border:1px solid #e5e7eb; border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,.08); padding:8px; pointer-events:auto;
  }
  /* Abre por hover e também quando .open é aplicado via JS */
  .fly-wrap:hover .flyout,
  .fly-wrap.open .flyout{ display:block; }

  .fly-hd{ font-size:11px; font-weight:700; color:#475569; padding:8px 10px 6px; text-transform:uppercase; letter-spacing:.04em; }
  .fly-mi{
    display:block; padding:8px 10px; border-radius:8px; color:#0f172a; text-decoration:none; font-weight:500;
  }
  .fly-mi:hover{ background:#f3f4f6; }
  .fly-mi.active{ background:#eef2ff; color:#1e3a8a; }
</style>

<aside class="rmenu">
  <div class="box">
    <div class="head">
      <div class="title">{{ $tituloTopo }}</div>
      @if($clienteNome)
        <div class="sub">{{ $clienteNome }}</div>
      @endif
    </div>

    <nav class="list" aria-label="Seções do concurso">
      @foreach ($items as $it)
        @php $isActive = ($activeKey === $it['key']) || ($it['key']==='inscricoes' && $isGroupInscricoesActive); @endphp

        @if($it['key'] === 'inscricoes')
          <div class="fly-wrap" aria-haspopup="true" aria-expanded="{{ $isGroupInscricoesActive ? 'true' : 'false' }}">
            <a href="{{ $it['url'] }}" class="mi has-caret {{ $isActive ? 'active' : '' }}">
              <i data-lucide="{{ $it['icon'] }}"></i>
              <span>{{ $it['label'] }}</span>
            </a>

            <div class="flyout">
              @foreach($fly as $op)
                @if(!empty($op['sep']))
                  <div class="fly-hd">{{ $op['label'] }}</div>
                @else
                  @php
                      $keys = is_string($op['key']) ? explode('|', $op['key']) : [];
                      $childActive = in_array($activeKey, $keys, true);
                  @endphp
                  <a href="{{ $op['url'] }}" class="fly-mi {{ $childActive ? 'active' : '' }}">{{ $op['label'] }}</a>
                @endif
              @endforeach
            </div>
          </div>
        @else
          <a href="{{ $it['url'] }}" class="mi {{ $isActive ? 'active' : '' }}">
            <i data-lucide="{{ $it['icon'] }}"></i>
            <span>{{ $it['label'] }}</span>
          </a>
        @endif
      @endforeach
    </nav>
  </div>
</aside>

@once
  {{-- Lucide Icons --}}
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      window.lucide?.createIcons();

      // Hover com tolerância (evita fechar instantâneo ao mover o mouse)
      const wrap = document.querySelector('.fly-wrap');
      if (!wrap) return;
      const panel = wrap.querySelector('.flyout');
      let hideTimer;

      const open  = () => { clearTimeout(hideTimer); wrap.classList.add('open'); };
      const close = () => { hideTimer = setTimeout(() => wrap.classList.remove('open'), 180); };

      wrap.addEventListener('mouseenter', open);
      wrap.addEventListener('mouseleave', close);
      panel.addEventListener('mouseenter', open);
      panel.addEventListener('mouseleave', close);

      // Ajuste de posicionamento para não estourar fora da viewport
      const clampInsideViewport = () => {
        const r = panel.getBoundingClientRect();
        const bottomOverflow = (r.bottom + 8) - window.innerHeight;
        panel.style.top = bottomOverflow > 0 ? `calc(0px - ${bottomOverflow}px)` : '0';
      };
      wrap.addEventListener('mouseenter', clampInsideViewport);
      window.addEventListener('resize', clampInsideViewport);
    });
  </script>
@endonce
