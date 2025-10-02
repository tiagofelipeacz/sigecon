@php
  /** @var \App\Models\Candidato $candidato */
  $ufs = $ufs ?? ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
  $v = fn($k,$d=null)=> old($k, data_get($candidato,$k,$d));
  $vBool = fn($k,$d=1)=> (int) old($k, (int) data_get($candidato,$k,$d));
  $fotoUrl = $candidato->foto_path ? asset('storage/'.$candidato->foto_path) : null;
@endphp

@if ($errors->any())
  <div class="mb-3 rounded border border-red-300 bg-red-50 p-3 text-red-800">
    <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif
@if (session('success'))
  <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
    {{ session('success') }}
  </div>
@endif

<style>
  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"],
  .form-min input[type="email"],
  .form-min input[type="date"],
  .form-min input[type="password"],
  .form-min input[type="file"],
  .form-min textarea,
  .form-min select{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .radio-row{ display:flex; gap:16px; align-items:center; }
  .full{ grid-column:1 / -1; }
  .muted{ color:#6b7280; font-size:12px; }
  .avatar{ width:110px; height:110px; object-fit:cover; border-radius:10px; border:1px solid #e5e7eb; background:#f8fafc; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

{{-- ===================== DADOS GERAIS ===================== --}}
<div class="card-min">
  <div class="hd">Dados Gerais</div>
  <div class="form-min">
    <label>Nome completo *</label>
    <input type="text" name="nome" required value="{{ $v('nome') }}" placeholder="Nome do candidato">

    <label>CPF *</label>
    <input type="text" name="cpf" required inputmode="numeric" maxlength="14"
           value="{{ \Illuminate\Support\Str::of($v('cpf'))->replaceMatches('~(\d{3})(\d{3})(\d{3})(\d{2})~', '$1.$2.$3-$4') }}"
           placeholder="000.000.000-00" data-mask="cpf">

    <label>E-mail</label>
    <input type="email" name="email" value="{{ $v('email') }}" placeholder="email@exemplo.com">

    <label>Data de Nascimento</label>
    @php
      $dn = $v('data_nascimento');
      if ($dn && preg_match('~^\d{4}-\d{2}-\d{2}$~', $dn)) {
        try { $dn = \Illuminate\Support\Carbon::parse($dn)->format('d/m/Y'); } catch(\Throwable $e) {}
      }
    @endphp
    <input type="text" name="data_nascimento" value="{{ $dn }}" placeholder="dd/mm/aaaa" data-mask="date">

    <label>Sexo</label>
    <select name="sexo">
      <option value="">-</option>
      <option value="M" @selected($v('sexo')==='M')>Masculino</option>
      <option value="F" @selected($v('sexo')==='F')>Feminino</option>
      <option value="O" @selected($v('sexo')==='O')>Outro</option>
    </select>

    <label>Estado Civil</label>
    <select name="estado_civil">
      <option value="">-</option>
      <option value="solteiro"    @selected($v('estado_civil')==='solteiro')>Solteiro(a)</option>
      <option value="casado"      @selected($v('estado_civil')==='casado')>Casado(a)</option>
      <option value="separado"    @selected($v('estado_civil')==='separado')>Separado(a)</option>
      <option value="divorciado"  @selected($v('estado_civil')==='divorciado')>Divorciado(a)</option>
      <option value="viuvo"       @selected($v('estado_civil')==='viuvo')>Viúvo(a)</option>
      <option value="outro"       @selected($v('estado_civil')==='outro')>Outro</option>
    </select>

    <label>Telefone</label>
    <input type="text" name="telefone" value="{{ $v('telefone') }}" placeholder="(00)0000-0000" data-mask="phone">

    <label>Celular</label>
    <input type="text" name="celular" value="{{ $v('celular') }}" placeholder="(00)00000-0000" data-mask="cel">

    <label>Status</label>
    <div class="radio-row">
      <label><input type="radio" name="status" value="1" {{ $vBool('status',1)===1 ? 'checked':'' }}> Ativo</label>
      <label><input type="radio" name="status" value="0" {{ $vBool('status',1)===0 ? 'checked':'' }}> Inativo</label>
    </div>
  </div>
</div>

{{-- ===================== FOTO ===================== --}}
<div class="card-min">
  <div class="hd">Foto</div>
  <div class="form-min">
    <label>Foto (imagem)</label>
    <input type="file" name="foto" accept="image/*" data-preview="#fotoPreview">
    <div class="full" style="display:flex; gap:12px; align-items:center;">
      <img id="fotoPreview" class="avatar" src="{{ $fotoUrl ?: '' }}" alt="Pré-visualização">
      <div class="muted">Formatos aceitos: JPG, PNG, GIF. Máx. 4 MB.</div>
    </div>
  </div>
</div>

{{-- ===================== CREDENCIAIS ===================== --}}
<div class="card-min">
  <div class="hd">Credenciais de Acesso</div>
  <div class="form-min">
    <label>Senha</label>
    <input type="password" name="password" autocomplete="new-password" placeholder="Preencha para alterar/criar">

    <label>Confirmar Senha</label>
    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repita a senha">
  </div>
</div>

{{-- ===================== DOCUMENTOS ===================== --}}
<div class="card-min">
  <div class="hd">Documentos</div>
  <div class="form-min">
    <label>Tipo de Documento</label>
    <select name="doc_tipo">
      <option value="">-</option>
      <option value="rg"  @selected($v('doc_tipo')==='rg')>RG</option>
      <option value="cnh" @selected($v('doc_tipo')==='cnh')>CNH</option>
      <option value="ctps"@selected($v('doc_tipo')==='ctps')>Carteira de Trabalho</option>
    </select>

    <label>Número</label>
    <input type="text" name="doc_numero" value="{{ $v('doc_numero') }}" placeholder="Número do documento">

    <label>Órgão Emissor</label>
    <input type="text" name="doc_orgao" value="{{ $v('doc_orgao') }}" placeholder="SSP, DETRAN, etc.">

    <label>UF (doc)</label>
    <select name="doc_uf">
      <option value="">-</option>
      @foreach($ufs as $uf)
        <option value="{{ $uf }}" @selected($v('doc_uf')===$uf)>{{ $uf }}</option>
      @endforeach
    </select>

    <label>Complemento</label>
    <input type="text" name="doc_complemento" value="{{ $v('doc_complemento') }}" placeholder="Série, livro, folha... (opcional)">

    <label>Categoria CNH</label>
    <input type="text" name="cnh_categoria" value="{{ $v('cnh_categoria') }}" placeholder="Ex.: B, AB, C...">
  </div>
</div>

{{-- ===================== ENDEREÇO ===================== --}}
<div class="card-min">
  <div class="hd">Endereço</div>
  <div class="form-min">
    <label>CEP</label>
    <input type="text" name="endereco_cep" value="{{ $v('endereco_cep') }}" placeholder="00000-000" data-mask="cep" data-cep-fetch="/api/cep">

    <label>Endereço (rua)</label>
    <input type="text" name="endereco_rua" value="{{ $v('endereco_rua') }}" placeholder="Rua, Av., Travessa...">

    <label>Número</label>
    <input type="text" name="endereco_numero" value="{{ $v('endereco_numero') }}" placeholder="Nº">

    <label>Complemento</label>
    <input type="text" name="endereco_complemento" value="{{ $v('endereco_complemento') }}" placeholder="Apto, bloco...">

    <label>Bairro</label>
    <input type="text" name="endereco_bairro" value="{{ $v('endereco_bairro') }}" placeholder="Bairro">

    <label>Cidade</label>
    <input type="text" name="cidade" value="{{ $v('cidade') }}" placeholder="Cidade">

    <label>Estado (UF)</label>
    <select name="estado">
      <option value="">-</option>
      @foreach($ufs as $uf)
        <option value="{{ $uf }}" @selected($v('estado')===$uf)>{{ $uf }}</option>
      @endforeach
    </select>
  </div>
</div>

{{-- ===================== OUTRAS INFORMAÇÕES ===================== --}}
<div class="card-min">
  <div class="hd">Outras Informações</div>
  <div class="form-min">
    <label>Nome da Mãe</label>
    <input type="text" name="nome_mae" value="{{ $v('nome_mae') }}">

    <label>Nome do Pai</label>
    <input type="text" name="nome_pai" value="{{ $v('nome_pai') }}">

    <label>Nacionalidade</label>
    <input type="text" name="nacionalidade" value="{{ $v('nacionalidade') }}" placeholder="Brasileira, ...">

    <label>Naturalidade (UF)</label>
    <select name="naturalidade_uf">
      <option value="">-</option>
      @foreach($ufs as $uf)
        <option value="{{ $uf }}" @selected($v('naturalidade_uf')===$uf)>{{ $uf }}</option>
      @endforeach
    </select>

    <label>Naturalidade (Cidade)</label>
    <input type="text" name="naturalidade_cidade" value="{{ $v('naturalidade_cidade') }}">

    <label>Ano de Chegada ao Brasil</label>
    <input type="text" name="nacionalidade_ano_chegada" inputmode="numeric" pattern="\d{4}" value="{{ $v('nacionalidade_ano_chegada') }}" placeholder="AAAA">

    <label>NIS (SISTAC)</label>
    <input type="text" name="sistac_nis" value="{{ $v('sistac_nis') }}">

    <label>Qtde. de Filhos</label>
    <input type="number" name="qt_filhos" min="0" max="50" value="{{ $v('qt_filhos', 0) }}">

    <label>ID Deficiência</label>
    <input type="number" name="id_deficiencia" min="1" step="1" value="{{ $v('id_deficiencia') }}" placeholder="(se aplicável)">

    <label class="full">Observações Internas</label>
    <textarea name="observacoes_internas" rows="4" class="full"
              placeholder="Notas internas da equipe...">{{ $v('observacoes_internas') }}</textarea>
  </div>
</div>

<div class="toolbar" style="display:flex; gap:.5rem;">
  <input type="hidden" name="action" value="save">
  <button class="btn" type="submit" onclick="this.form.action.value='save'">Salvar</button>
  <button class="btn" type="submit" onclick="this.form.action.value='save_close'">Salvar e Fechar</button>
  <a class="btn" href="{{ route('admin.candidatos.index') }}">Cancelar</a>
</div>

{{-- Helpers JS (máscaras simples + preview + CEP) --}}
<script>
(function(){
  const fm = document.currentScript.closest('form');

  // Preview da foto
  const file = fm.querySelector('input[name="foto"]');
  if (file) {
    file.addEventListener('change', e => {
      const [f] = e.target.files || [];
      if (f) {
        const url = URL.createObjectURL(f);
        const img = fm.querySelector(file.getAttribute('data-preview') || '#fotoPreview');
        if (img) img.src = url;
      }
    });
  }

  // Máscaras bem simples (sem libs)
  const maskers = {
    cpf(v){ v = v.replace(/\D/g,'').slice(0,11);
      return v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2'); },
    cep(v){ v = v.replace(/\D/g,'').slice(0,8);
      return v.replace(/(\d{5})(\d)/,'$1-$2'); },
    phone(v){ v = v.replace(/\D/g,'').slice(0,10);
      return v.replace(/(\d{2})(\d{4})(\d{0,4})/,'($1)$2-$3').replace(/-$/,''); },
    cel(v){ v = v.replace(/\D/g,'').slice(0,11);
      return v.replace(/(\d{2})(\d{5})(\d{0,4})/,'($1)$2-$3').replace(/-$/,''); },
    date(v){ v = v.replace(/\D/g,'').slice(0,8);
      return v.replace(/(\d{2})(\d)/,'$1/$2').replace(/(\d{2})(\d)/,'$1/$2'); },
  };
  fm.querySelectorAll('[data-mask]').forEach(inp=>{
    const type = inp.getAttribute('data-mask');
    inp.addEventListener('input', () => {
      inp.value = (maskers[type] ? maskers[type](inp.value) : inp.value);
    });
  });

  // CEP -> auto preencher
  const cepInput = fm.querySelector('input[name="endereco_cep"]');
  if (cepInput) {
    cepInput.addEventListener('change', async () => {
      const raw = cepInput.value.replace(/\D/g,'');
      if (raw.length !== 8) return;
      const url = (cepInput.getAttribute('data-cep-fetch') || '/api/cep') + '?cep=' + raw;
      try{
        const r = await fetch(url);
        if(!r.ok) return;
        const j = await r.json();
        fm.querySelector('input[name="endereco_rua"]')?.value     = j.logradouro_completo || j.logradouro || j.rua || '';
        fm.querySelector('input[name="endereco_bairro"]')?.value  = j.bairro_nome || j.bairro || '';
        fm.querySelector('input[name="cidade"]')?.value           = j.cidade || j.localidade || '';
        if (j.id_estado || j.uf) {
          const uf = (j.uf || j.id_estado || '').toString().toUpperCase();
          const sel = fm.querySelector('select[name="estado"]');
          if (sel) sel.value = uf;
        }
      }catch(e){}
    });
  }
})();
</script>
