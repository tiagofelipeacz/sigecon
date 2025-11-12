{{-- resources/views/site/candidato/inscricoes/comprovante_pdf.blade.php --}}
@php
    use Illuminate\Support\Str;

    // Concurso
    $concursoTitulo = $concurso->titulo
        ?? $concurso->nome
        ?? ('Concurso #'.$insc->concurso_id);

    $concursoCodigo = $concurso->codigo ?? null;
    $concursoOrganizador = $concurso->organizadora
        ?? $concurso->orgao
        ?? null;

    // Cargo
    $cargoNome = $cargo->nome ?? '—';

    // Datas
    $dataInsc = $insc->created_at
        ? $insc->created_at->format('d/m/Y H:i')
        : '—';

    // Número da inscrição
    $numeroInscricao = $insc->numero ?? $insc->id;

    // Status
    $status = strtoupper($insc->status ?? 'CONFIRMADA');

    // Candidato
    $nomeCandidato = $insc->nome_candidato ?? $user->nome;
    $cpfCandidato  = $insc->cpf ?? $user->cpf;
    $nasc          = $insc->nascimento ?? $user->data_nascimento ?? null;
    $dataNasc      = $nasc ? \Carbon\Carbon::parse($nasc)->format('d/m/Y') : '—';

    // Modalidade: usa exatamente o texto salvo na inscrição, com fallback
    $modalidade    = $insc->modalidade ?: 'Ampla concorrência';
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante de inscrição {{ $numeroInscricao }}</title>
    <style>
        *{ box-sizing:border-box; }
        body{
            margin:0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size:12px;
            color:#111827;
        }
        .page{
            padding:24px 28px;
        }
        .header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:18px;
            border-bottom:2px solid #e5e7eb;
            padding-bottom:8px;
        }
        .brand-title{
            font-size:16px;
            font-weight:bold;
            text-transform:uppercase;
        }
        .brand-sub{
            font-size:11px;
            color:#4b5563;
        }
        .title{
            text-align:center;
            margin:10px 0 14px;
            font-size:15px;
            font-weight:bold;
            text-transform:uppercase;
        }

        .box{
            border:1px solid #e5e7eb;
            border-radius:6px;
            padding:10px 12px;
            margin-bottom:10px;
        }
        .box-title{
            font-size:11px;
            font-weight:bold;
            text-transform:uppercase;
            margin:0 0 6px;
        }
        .box-row{
            display:flex;
            justify-content:space-between;
            gap:12px;
        }
        .field-group{
            margin-bottom:4px;
            font-size:12px;
        }
        .field-label{
            font-weight:bold;
        }
        .numero-inscricao{
            font-size:20px;
            font-weight:bold;
            letter-spacing:0.10em;
        }
        .status-pill{
            display:inline-block;
            padding:3px 8px;
            border-radius:999px;
            border:1px solid #16a34a;
            background:#dcfce7;
            color:#166534;
            font-size:10px;
            font-weight:bold;
        }
        .footer{
            margin-top:18px;
            font-size:10px;
            color:#6b7280;
            border-top:1px solid #e5e7eb;
            padding-top:8px;
            text-align:center;
        }
        table{
            width:100%;
            border-collapse:collapse;
            font-size:12px;
        }
        th, td{
            padding:4px 3px;
            border:1px solid #e5e7eb;
            text-align:left;
        }
        th{
            background:#f3f4f6;
            font-size:11px;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <div>
            <div class="brand-title">
                {{ $concursoOrganizador ?? 'Banca Organizadora' }}
            </div>
            <div class="brand-sub">
                Comprovante de inscrição em concurso / processo seletivo
            </div>
        </div>
        <div style="text-align:right; font-size:11px;">
            <div>Emitido em: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="title">
        COMPROVANTE DE INSCRIÇÃO
    </div>

    {{-- Dados da inscrição --}}
    <div class="box">
        <div class="box-row" style="align-items:center;">
            <div>
                <div class="field-label" style="font-size:11px; text-transform:uppercase;">
                    Nº de inscrição
                </div>
                <div class="numero-inscricao">{{ $numeroInscricao }}</div>
            </div>
            <div style="text-align:right;">
                <span class="status-pill">{{ $status }}</span>
            </div>
        </div>

        <div class="field-group" style="margin-top:8px;">
            <span class="field-label">Concurso:</span>
            {{ $concursoTitulo }}
        </div>
        <div class="field-group">
            @if($concursoCodigo)
                <span class="field-label">Código:</span> {{ $concursoCodigo }} ·
            @endif
            <span class="field-label">ID edital:</span> {{ $insc->concurso_id }}
        </div>
        <div class="field-group">
            <span class="field-label">Cargo:</span> {{ $cargoNome }}
        </div>
        <div class="field-group">
            <span class="field-label">Modalidade:</span> {{ $modalidade }}
            · <span class="field-label">Data da inscrição:</span> {{ $dataInsc }}
        </div>
    </div>

    {{-- Dados do candidato --}}
    <div class="box">
        <div class="box-title">Dados do candidato</div>
        <table>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Data de nascimento</th>
            </tr>
            <tr>
                <td>{{ $nomeCandidato }}</td>
                <td>{{ $cpfCandidato }}</td>
                <td>{{ $dataNasc }}</td>
            </tr>
        </table>
    </div>

    {{-- Observações --}}
    <div class="box">
        <div class="box-title">Observações</div>
        <p style="margin:0; font-size:11px; line-height:1.4;">
            Este comprovante confirma a inscrição do candidato no concurso indicado acima,
            de acordo com os dados fornecidos no ato da inscrição.
            <br>
            Recomenda-se guardar este documento até o término de todas as etapas do certame.
        </p>
    </div>

    <div class="footer">
        Documento gerado eletronicamente. Não é necessária assinatura.
    </div>

</div>
</body>
</html>
