{{-- Proxy de formulário: usado apenas se seu _form real não for encontrado.
Coloque o SEU arquivo em um dos caminhos abaixo (o primeiro que existir será usado):
- resources/views/admin/concursos/_form.blade.php
- resources/views/admin/concursos/partials/_form.blade.php
- resources/views/admin/concursos/form.blade.php
- resources/views/concursos/_form.blade.php
- resources/views/concursos/partials/_form.blade.php
--}}
<div class="empty" style="margin:16px 0">
  <div style="font-weight:600;margin-bottom:6px">Formulário não encontrado</div>
  <div style="color:#9fb0c7">
    Coloque o arquivo <code>_form.blade.php</code> do seu projeto em um dos caminhos listados acima
    ou ajuste o caminho no <code>@includeFirst</code> dos arquivos <code>create</code>/<code>edit</code>.
  </div>
</div>
