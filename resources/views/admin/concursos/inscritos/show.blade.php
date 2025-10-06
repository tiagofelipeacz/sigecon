@extends('layouts.sigecon')
@section('title', 'Resumo da Inscrição - SIGECON')

@section('content')
@php
  $num     = $inscricao->numero ?? $inscricao->id;
  $stKey   = $inscricao->status ?? '';
  $stLabel = $STATUS_LBL[$stKey] ?? ucfirst($stKey ?: '-');

  $cpfRaw = (string) ($inscricao->cpf ?? '');
  $cpfFmt = (preg_match('/^\d{11}$/', $cpfRaw))
      ? (substr($cpfRaw,0,3).'.'.substr($cpfRaw,3,3).'.'.substr($cpfRaw,6,3).'-'.substr($cpfRaw,9,2))
      : $cpfRaw;

  $modalidade = $inscricao->modalidade ?: 'ampla';
  $modalidadeLabel = $modalidade ? ucfirst($modalidade) : 'Ampla';

  $cargoNome       = $inscricao->cargo_nome ?? ('#'.$inscricao->cargo_id);
  $localidadeNome  = $localidade ?? '-';

  // ===== VALOR DA INSCRIÇÃO =====
  // Preferência: variável vinda do controller -> colunas comuns da própria inscrição
  $valorRaw = null;
  if (isset($valorInscricao) && $valorInscricao !== null) {
      $valorRaw = $valorInscricao;
  } else {
      $valorRaw = $inscricao->valor_inscricao
               ?? $inscricao->taxa_inscricao
               ?? $inscricao->taxa
               ?? $inscricao->valor
               ?? null;
  }
  $valorFmt = is_null($valorRaw)
      ? '-'
      : ((is_numeric($valorRaw) && (float)$valorRaw == 0.0)
          ? 'Isento'
          : ('R$ '.number_format((float)$valorRaw, 2, ',', '.')));

  $contEmail = $contatos['email'] ?? null;
  $contTel   = $contatos['telefone'] ?? null;
  $contCel   = $contatos['celular'] ?? null;

  $remuFmt = null;
  if (!empty($vaga['remuneracao'])) {
    $numRem = is_numeric($vaga['remuneracao']) ? (float)$vaga['remuneracao'] : null;
    $remuFmt = $numRem !== null ? ('R$ '.number_format($numRem, 2, ',', '.')) : (string)$vaga['remuneracao'];
  }
@endphp

<style>
  .gc-page{display:grid;grid-template-columns:260px 1fr;gap:16px;}
  .gc-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.03);}
  .gc-body{padding:16px;}
  .grid{display:grid;gap:10px;}
  .g-2{grid-template-columns:1fr 1fr;}
  .g-3{grid-template-columns:1fr 1fr 1fr;}
  .g-4{grid-template-columns:repeat(4,1fr);}
  .tag{font-size:12px;color:#6b7280;}
  .val{font-weight:600;}
  .hr{height:1px;background:#f3f4f6;margin:12px 0;}
  .btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;padding:8px 10px;border-radius:8px;text-decoration:none;cursor:pointer;}
  .btn.primary{background:#111827;color:#fff;border-color:#111827;}
  .btn.link{background:transparent;border-color:transparent;color:#111827;}
  .chip{background:#eef2ff;color:#3730a3;padding:2px 8px;border-radius:999px;font-size:12px;display:inline-block;margin-right:6px;margin-bottom:6px;}
  .muted{color:#6b7280}
  .mono{font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;}
  .table{width:100%;border-collapse:separate;border-spacing:0 6px}
  .table td{padding:8px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-left-width:0}
  .table td:first-child{border-left-width:1px;border-radius:8px 0 0 8px;width:220px;color:#6b7280}
  .table td:last-child{border-radius:0 8px 8px 0;}
</style>

<div class="gc-page">
  {{-- Menu lateral do concurso --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscritos'
    ])
  </div>

  {{-- Conteúdo --}}
  <div>
    <div class="gc-card">
      <div class="gc-body">

        {{-- Cabeçalho topo --}}
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          <div>
            <div class="tag">Concurso</div>
            <div class="val">#{{ $concurso->id }} - {{ $concurso->titulo ?? $concurso->nome ?? 'Concurso' }}</div>
          </div>
          <div style="display:flex;gap:8px">
            <a href="{{ route('admin.concursos.inscritos.index', $concurso) }}" class="btn link">Voltar</a>
          </div>
        </div>

        <div class="hr"></div>

        {{-- Linha principal --}}
        <div class="grid g-4">
          <div>
            <div class="tag">Inscrição</div>
            <div class="val">#{{ $num }}</div>
            <div class="muted">ID interno: {{ $inscricao->id }}</div>
          </div>
          <div>
            <div class="tag">Situação</div>
            <div class="val">{{ $stLabel }}</div>
          </div>
          <div>
            <div class="tag">Data/Hora</div>
            <div class="val">{{ \Carbon\Carbon::parse($inscricao->created_at)->format('d/m/Y H:i') }}</div>
          </div>
          <div>
            <div class="tag">Valor da inscrição</div>
            <div class="val">{{ $valorFmt }}</div>
          </div>
        </div>

        <div class="hr"></div>

        {{-- Candidato --}}
        <div class="grid g-3">
          <div>
            <div class="tag">Candidato</div>
            <div class="val">{{ $inscricao->nome_candidato }}</div>
          </div>
          <div>
            <div class="tag">CPF</div>
            <div class="val">{{ $cpfFmt ?: '-' }}</div>
          </div>
          <div>
            <div class="tag">Nascimento</div>
            <div class="val">{{ $inscricao->nascimento ? \Carbon\Carbon::parse($inscricao->nascimento)->format('d/m/Y') : '-' }}</div>
          </div>
        </div>

        {{-- Contatos (se houver) --}}
        @if($contEmail || $contTel || $contCel)
          <table class="table" style="margin-top:10px">
            @if($contEmail)
              <tr><td>E-mail</td><td class="val">{{ $contEmail }}</td></tr>
            @endif
            @if($contTel)
              <tr><td>Telefone</td><td class="val">{{ $contTel }}</td></tr>
            @endif
            @if($contCel)
              <tr><td>Celular</td><td class="val">{{ $contCel }}</td></tr>
            @endif
          </table>
        @endif

        <div class="hr"></div>

        {{-- Vaga / Localidade / Modalidade --}}
        <div class="grid g-3">
          <div>
            <div class="tag">Vaga</div>
            <div class="val">{{ $cargoNome }}</div>
            <div class="muted">Cargo ID: {{ $inscricao->cargo_id }}</div>
          </div>
          <div>
            <div class="tag">Localidade</div>
            <div class="val">{{ $localidadeNome }}</div>
            @if(isset($inscricao->item_id) && $inscricao->item_id)
              <div class="muted">Item/Localidade ID: {{ $inscricao->item_id }}</div>
            @elseif(isset($inscricao->localidade_id) && $inscricao->localidade_id)
              <div class="muted">Localidade ID: {{ $inscricao->localidade_id }}</div>
            @endif
          </div>
          <div>
            <div class="tag">Modalidade</div>
            <div class="val">{{ $modalidadeLabel }}</div>
          </div>
        </div>

        {{-- Detalhes da vaga (se houver) --}}
        @if(!empty($vaga['escolaridade']) || !empty($vaga['carga_horaria']) || !empty($vaga['remuneracao']) || $provaCidade || $provaUF)
          <table class="table" style="margin-top:10px">
            @if(!empty($vaga['escolaridade']))
              <tr><td>Escolaridade exigida</td><td class="val">{{ $vaga['escolaridade'] }}</td></tr>
            @endif
            @if(!empty($vaga['carga_horaria']))
              <tr><td>Carga horária</td><td class="val">{{ $vaga['carga_horaria'] }}</td></tr>
            @endif
            @if(!empty($vaga['remuneracao']))
              <tr><td>Remuneração</td><td class="val">{{ $remuFmt }}</td></tr>
            @endif
            @if($provaCidade || $provaUF)
              <tr>
                <td>Cidade/UF de Prova</td>
                <td class="val">
                  {{ trim(($provaCidade ?? '').' '.($provaUF ? '/ '.$provaUF : '')) ?: '-' }}
                </td>
              </tr>
            @endif
          </table>
        @endif

        <div class="hr"></div>

        {{-- Condições especiais --}}
        <div>
          <div class="tag" style="margin-bottom:6px">Condições Especiais</div>
          @if(!empty($condicoes))
            @foreach($condicoes as $c)
              <span class="chip">{{ $c }}</span>
            @endforeach
          @else
            <div class="muted">Nenhuma selecionada</div>
          @endif
        </div>

        {{-- Observações internas (se houver) --}}
        @if(!empty($observacoes))
          <div class="hr"></div>
          <div>
            <div class="tag" style="margin-bottom:6px">Observações</div>
            <div class="val" style="font-weight:500">{{ $observacoes }}</div>
          </div>
        @endif

        {{-- Pagamento (se encontrado) --}}
        @if(!empty($pagamento))
          <div class="hr"></div>
          <div>
            <div class="tag" style="margin-bottom:6px">Pagamento</div>
            <table class="table">
              @if(!empty($pagamento['status']))
                <tr><td>Status</td><td class="val">{{ ucfirst($pagamento['status']) }}</td></tr>
              @endif
              @if(!empty($pagamento['vencimento']))
                <tr><td>Vencimento</td><td class="val">
                  {{ \Illuminate\Support\Str::is('*-*', (string)$pagamento['vencimento']) 
                      ? \Carbon\Carbon::parse($pagamento['vencimento'])->format('d/m/Y') 
                      : $pagamento['vencimento'] }}
                </td></tr>
              @endif
              @if(isset($pagamento['valor']) && $pagamento['valor'] !== null && $pagamento['valor'] !== '')
                <tr><td>Valor</td><td class="val">
                  @php
                    $pvIsNum = is_numeric($pagamento['valor']);
                    $pvFmt   = $pvIsNum ? number_format($pagamento['valor'],2,',','.') : (string)$pagamento['valor'];
                  @endphp
                  {{ $pvIsNum ? 'R$ '.$pvFmt : $pvFmt }}
                </td></tr>
              @endif
              @if(!empty($pagamento['forma']))
                <tr><td>Forma</td><td class="val">{{ ucfirst($pagamento['forma']) }}</td></tr>
              @endif
              @if(!empty($pagamento['linha_digitavel']))
                <tr><td>Linha digitável</td><td class="val mono">{{ $pagamento['linha_digitavel'] }}</td></tr>
              @endif
              @if(!empty($pagamento['txid']))
                <tr><td>TXID / Nosso número</td><td class="val mono">{{ $pagamento['txid'] }}</td></tr>
              @endif
              @if(!empty($pagamento['url']))
                <tr><td>Documento</td><td class="val"><a href="{{ $pagamento['url'] }}" target="_blank" class="btn">Abrir</a></td></tr>
              @endif
            </table>
          </div>
        @endif

        {{-- Histórico (se houver) --}}
        @if(!empty($historico))
          <div class="hr"></div>
          <div>
            <div class="tag" style="margin-bottom:6px">Histórico</div>
            <table class="table">
              @foreach($historico as $h)
                <tr>
                  <td>{{ $h['quando'] ? \Carbon\Carbon::parse($h['quando'])->format('d/m/Y H:i') : '-' }}</td>
                  <td class="val">
                    {{ $h['status'] ? 'Status: '.ucfirst($h['status']) : '—' }}
                    @if(!empty($h['texto']))
                      <div class="muted" style="font-weight:400">{{ $h['texto'] }}</div>
                    @endif
                    @if(!empty($h['user_nome']))
                      <div class="muted" style="font-weight:400">por {{ $h['user_nome'] }}</div>
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>
          </div>
        @endif

        <div class="hr"></div>

        {{-- Ações --}}
        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap">
          @php
            $rotaBoleto = null;
            foreach (['admin.concursos.inscritos.boleto','admin.financeiro.boletos.gerar','admin.boletos.gerar'] as $r) {
              if (\Illuminate\Support\Facades\Route::has($r)) { $rotaBoleto = $r; break; }
            }

            $rotaComprovante = null;
            foreach (['admin.concursos.inscritos.comprovante','admin.concursos.inscricoes.comprovante'] as $r) {
              if (\Illuminate\Support\Facades\Route::has($r)) { $rotaComprovante = $r; break; }
            }

            $rotaEditar = \Illuminate\Support\Facades\Route::has('admin.concursos.inscritos.edit')
              ? 'admin.concursos.inscritos.edit' : null;
          @endphp

          @if($rotaBoleto && ($inscricao->status ?? '') !== 'confirmada')
            <a href="{{ route($rotaBoleto, [$concurso, $inscricao->id]) }}" class="btn primary">Gerar boleto</a>
          @endif

          @if($rotaComprovante)
            <a href="{{ route($rotaComprovante, [$concurso, $inscricao->id]) }}" class="btn">Comprovante</a>
          @endif

          @if($rotaEditar)
            <a href="{{ route($rotaEditar, [$concurso, $inscricao->id]) }}" class="btn">Editar inscrição</a>
          @endif

          <a href="{{ route('admin.concursos.inscritos.index', $concurso) }}" class="btn">Concluir</a>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
