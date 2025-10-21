<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SiteSettingsController extends Controller
{
    /**
     * Nome da tabela de configurações (primeira que existir).
     */
    private function tableName(): string
    {
        foreach (['site_settings','settings','configs','configurations'] as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        // padrão
        return 'site_settings';
    }

    /**
     * Lista de colunas da tabela.
     */
    private function cols(string $t): array
    {
        try { return Schema::getColumnListing($t); } catch (\Throwable $e) { return []; }
        // Nota: em dev/local Schema usa information_schema; se falhar, devolve vazio
    }

    /**
     * Converte um caminho relativo/absoluto para URL pública.
     */
    private function publicUrlFromAny(string $p): string
    {
        $p = trim(str_replace('\\','/',$p));
        if ($p === '') return '';
        if (Str::startsWith($p, ['http://','https://','data:image'])) {
            return $p;
        }
        if (Str::startsWith($p, ['/storage/','storage/'])) {
            return asset(ltrim($p,'/'));
        }
        $norm = ltrim($p,'/');
        if (Str::startsWith($norm,'public/')) $norm = substr($norm,7);

        if (Storage::disk('public')->exists($norm)) {
            return asset('storage/'.$norm);
        }
        if (file_exists(public_path($p)))               return asset($p);
        if (file_exists(public_path($norm)))            return asset($norm);
        if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);

        // último recurso: assume que está em storage público
        return asset('storage/'.$norm);
    }

    /**
     * Carrega as configurações atuais em array com defaults seguros.
     */
    private function getSiteArray(): array
    {
        $site = [
            'brand'         => 'GestaoConcursos',
            'primary'       => '#0f172a',
            'primary_color' => '#0f172a',
            'accent'        => '#111827',
            'accent_color'  => '#111827',
            'banner_title'  => 'Concursos e Processos Seletivos',
            'banner_sub'    => 'Inscreva-se, acompanhe publicações e consulte resultados.',
            'banner_url'    => null,
            'banner_path'   => null,
            // LOGO
            'logo_url'      => null,
            'logo_path'     => null,
        ];

        $t = $this->tableName();
        if (!Schema::hasTable($t)) {
            return $site;
        }

        // busca registro (id=1 ou o primeiro)
        $row = null;
        try {
            if (Schema::hasColumn($t,'id')) {
                $row = DB::table($t)->where('id',1)->first() ?? DB::table($t)->first();
            } else {
                $row = DB::table($t)->first();
            }
        } catch (\Throwable $e) {
            return $site;
        }

        if (!$row) return $site;

        // Map de cores (aceita primary OU primary_color; accent OU accent_color)
        $site['brand']         = (string)($row->brand ?? $site['brand']);
        $site['primary_color'] = (string)($row->primary_color ?? $row->primary ?? $site['primary_color']);
        $site['primary']       = $site['primary_color'];
        $site['accent_color']  = (string)($row->accent_color  ?? $row->accent  ?? $site['accent_color']);
        $site['accent']        = $site['accent_color'];

        $site['banner_title']  = (string)($row->banner_title ?? $site['banner_title']);
        $site['banner_sub']    = (string)($row->banner_sub   ?? $site['banner_sub'] );

        // Banner: aceita banner_url ou banner_path / banner / image / hero_image / hero_url
        $bannerCand = null;
        foreach (['banner_url','banner_path','banner','image','hero_image','hero_url'] as $k) {
            if (!empty($row->{$k})) { $bannerCand = trim((string)$row->{$k}); break; }
        }
        if ($bannerCand) {
            $site['banner_url']  = $this->publicUrlFromAny($bannerCand);
            $site['banner_path'] = $bannerCand;
        }

        // LOGO: aceita várias colunas
        $logoCand = null;
        foreach (['logo_url','logo_path','logo','logotipo','logo_image','site_logo','brand_logo','header_logo'] as $k) {
            if (!empty($row->{$k})) { $logoCand = trim((string)$row->{$k}); break; }
        }
        if ($logoCand) {
            $site['logo_url']  = $this->publicUrlFromAny($logoCand);
            $site['logo_path'] = $logoCand;
        }

        return $site;
    }

    /**
     * Página de edição.
     */
    public function edit(Request $request)
    {
        $site = $this->getSiteArray();

        if (View::exists('admin.config.site')) {
            return view('admin.config.site', compact('site'));
        }

        // Fallback simples (se a view ainda não existir)
        return response($this->fallbackHtml($request, $site));
    }

    /**
     * Salvar/atualizar configurações.
     * Atenção: NÃO mexe no banner (nem na logo) se usuário não enviou novo arquivo/URL.
     */
    public function update(Request $request)
    {
        $t = $this->tableName();
        $cols = $this->cols($t);

        // Normaliza entradas
        $brand         = trim((string)$request->input('brand',''));
        $primary       = trim((string)$request->input('primary',''));
        $primaryColor  = trim((string)$request->input('primary_color',''));
        $accent        = trim((string)$request->input('accent',''));
        $accentColor   = trim((string)$request->input('accent_color',''));
        $bannerTitle   = trim((string)$request->input('banner_title',''));
        $bannerSub     = trim((string)$request->input('banner_sub',''));
        $bannerUrlIn   = trim((string)$request->input('banner_url',''));
        // LOGO
        $logoUrlIn     = trim((string)$request->input('logo_url',''));

        $hasBannerUpload = $request->hasFile('banner') && $request->file('banner')->isValid();
        $hasBannerUrl    = $bannerUrlIn !== '';

        $hasLogoUpload   = $request->hasFile('logo') && $request->file('logo')->isValid();
        $hasLogoUrl      = $logoUrlIn !== '';

        // Monta payload APENAS com campos presentes na tabela
        $payload = [];

        // brand
        if (in_array('brand', $cols, true)) {
            $payload['brand'] = $brand;
        }

        // primary / primary_color
        if (in_array('primary_color', $cols, true)) {
            $payload['primary_color'] = $primaryColor ?: ($primary ?: null);
        } elseif (in_array('primary', $cols, true)) {
            $payload['primary'] = $primary ?: ($primaryColor ?: null);
        }

        // accent / accent_color
        if (in_array('accent_color', $cols, true)) {
            $payload['accent_color'] = $accentColor ?: ($accent ?: null);
        } elseif (in_array('accent', $cols, true)) {
            $payload['accent'] = $accent ?: ($accentColor ?: null);
        }

        // textos
        if (in_array('banner_title', $cols, true)) $payload['banner_title'] = $bannerTitle;
        if (in_array('banner_sub',   $cols, true)) $payload['banner_sub']   = $bannerSub;

        // >>> BANNER: só altera se o usuário realmente enviou arquivo/URL <<<
        if ($hasBannerUpload || $hasBannerUrl) {
            if ($hasBannerUpload) {
                $stored = $request->file('banner')->store('site', ['disk' => 'public']); // ex: site/abc.jpg

                // zera banner_url se existir, pois agora o "dono" é o arquivo
                if (in_array('banner_url', $cols, true)) {
                    $payload['banner_url'] = null;
                }

                // escolhe a melhor coluna para guardar o path
                if (in_array('banner_path', $cols, true)) {
                    $payload['banner_path'] = $stored;
                } elseif (in_array('banner', $cols, true)) {
                    $payload['banner'] = $stored;
                } elseif (in_array('image', $cols, true)) {
                    $payload['image'] = $stored;
                } elseif (in_array('hero_image', $cols, true)) {
                    $payload['hero_image'] = $stored;
                }
            } else { // $hasBannerUrl
                $url = $bannerUrlIn;

                if (in_array('banner_url', $cols, true)) {
                    $payload['banner_url'] = $url;
                } elseif (in_array('banner_path', $cols, true)) {
                    $payload['banner_path'] = $url; // ok armazenar URL aqui, o loader trata
                } elseif (in_array('banner', $cols, true)) {
                    $payload['banner'] = $url;
                } elseif (in_array('image', $cols, true)) {
                    $payload['image'] = $url;
                } elseif (in_array('hero_image', $cols, true)) {
                    $payload['hero_image'] = $url;
                }
            }
        }
        // Se NÃO houve upload/URL, não encosta em nenhum campo de banner — preserva!

        // >>> LOGO: só altera se o usuário realmente enviou arquivo/URL <<<
        if ($hasLogoUpload || $hasLogoUrl) {
            if ($hasLogoUpload) {
                $stored = $request->file('logo')->store('site', ['disk' => 'public']); // ex: site/logo-xyz.png

                // zera logo_url se existir, pois agora o "dono" é o arquivo
                if (in_array('logo_url', $cols, true)) {
                    $payload['logo_url'] = null;
                }

                // escolhe a melhor coluna para guardar o path
                if (in_array('logo_path', $cols, true)) {
                    $payload['logo_path'] = $stored;
                } elseif (in_array('logo', $cols, true)) {
                    $payload['logo'] = $stored;
                } elseif (in_array('logotipo', $cols, true)) {
                    $payload['logotipo'] = $stored;
                } elseif (in_array('logo_image', $cols, true)) {
                    $payload['logo_image'] = $stored;
                } elseif (in_array('site_logo', $cols, true)) {
                    $payload['site_logo'] = $stored;
                } elseif (in_array('brand_logo', $cols, true)) {
                    $payload['brand_logo'] = $stored;
                } elseif (in_array('header_logo', $cols, true)) {
                    $payload['header_logo'] = $stored;
                }
            } else { // $hasLogoUrl
                $url = $logoUrlIn;

                if (in_array('logo_url', $cols, true)) {
                    $payload['logo_url'] = $url;
                } elseif (in_array('logo_path', $cols, true)) {
                    $payload['logo_path'] = $url; // loader resolve como público
                } elseif (in_array('logo', $cols, true)) {
                    $payload['logo'] = $url;
                } elseif (in_array('logotipo', $cols, true)) {
                    $payload['logotipo'] = $url;
                } elseif (in_array('logo_image', $cols, true)) {
                    $payload['logo_image'] = $url;
                } elseif (in_array('site_logo', $cols, true)) {
                    $payload['site_logo'] = $url;
                } elseif (in_array('brand_logo', $cols, true)) {
                    $payload['brand_logo'] = $url;
                } elseif (in_array('header_logo', $cols, true)) {
                    $payload['header_logo'] = $url;
                }
            }
        }
        // Se NÃO houve upload/URL, não encosta em nenhum campo da logo — preserva!

        // timestamps (se existirem)
        $now = now();
        if (in_array('updated_at', $cols, true)) $payload['updated_at'] = $now;
        $hasCreated = in_array('created_at', $cols, true);

        // upsert (assume id=1 quando existir coluna id)
        if (Schema::hasColumn($t,'id')) {
            $exists = DB::table($t)->where('id',1)->exists();
            if (!$exists) {
                $insert = $payload + ['id' => 1];
                if ($hasCreated) $insert['created_at'] = $now;
                DB::table($t)->insert($insert);
            } else {
                DB::table($t)->where('id',1)->update($payload);
            }
        } else {
            // Sem coluna id: atualiza o primeiro, senão insere
            $first = DB::table($t)->first();
            if ($first) {
                // Atualiza "tudo" – cuidado em bases com várias linhas
                DB::table($t)->limit(1)->update($payload);
            } else {
                if ($hasCreated) $payload['created_at'] = $now;
                DB::table($t)->insert($payload);
            }
        }

        return back()->with('ok', 'Configurações do site atualizadas.');
    }

    /**
     * Remover APENAS o banner (quando clicar no botão "Remover banner").
     * Aceita DELETE em:
     *  - /admin/config/site/banner
     *  - /admin/site/banner
     */
    public function destroyBanner(Request $request)
    {
        $t = $this->tableName();
        if (!Schema::hasTable($t)) {
            return back()->with('ok', 'Nada para remover.');
        }

        // Lê a linha para tentar excluir arquivo físico
        $row = Schema::hasColumn($t,'id')
            ? (DB::table($t)->where('id',1)->first() ?? DB::table($t)->first())
            : DB::table($t)->first();

        $cols = $this->cols($t);
        $payload = [];

        foreach (['banner_url','banner_path','banner','image','hero_image'] as $c) {
            if (in_array($c, $cols, true)) $payload[$c] = null;
        }
        if (in_array('updated_at', $cols, true)) $payload['updated_at'] = now();

        // Exclui arquivo físico se havia path em disco público
        if ($row) {
            $cand = null;
            foreach (['banner_path','banner','image','hero_image'] as $c) {
                if (!empty($row->{$c})) { $cand = $row->{$c}; break; }
            }
            if ($cand) {
                $p = ltrim(str_replace('\\','/',$cand), '/');
                if (Str::startsWith($p,'public/')) $p = substr($p,7);
                try { if (Storage::disk('public')->exists($p)) Storage::disk('public')->delete($p); } catch (\Throwable $e) {}
            }
        }

        if (Schema::hasColumn($t,'id')) {
            DB::table($t)->where('id',1)->update($payload);
        } else {
            DB::table($t)->limit(1)->update($payload);
        }

        return back()->with('ok', 'Banner removido com sucesso.');
    }

    /**
     * Remover APENAS a LOGO (quando clicar no botão "Remover logo").
     * Aceita DELETE em:
     *  - /admin/config/site/logo
     *  - /admin/site/logo
     */
    public function destroyLogo(Request $request)
    {
        $t = $this->tableName();
        if (!Schema::hasTable($t)) {
            return back()->with('ok', 'Nada para remover.');
        }

        // Lê a linha para tentar excluir arquivo físico
        $row = Schema::hasColumn($t,'id')
            ? (DB::table($t)->where('id',1)->first() ?? DB::table($t)->first())
            : DB::table($t)->first();

        $cols = $this->cols($t);
        $payload = [];

        foreach (['logo_url','logo_path','logo','logotipo','logo_image','site_logo','brand_logo','header_logo'] as $c) {
            if (in_array($c, $cols, true)) $payload[$c] = null;
        }
        if (in_array('updated_at', $cols, true)) $payload['updated_at'] = now();

        // Exclui arquivo físico se havia path em disco público
        if ($row) {
            $cand = null;
            foreach (['logo_path','logo','logotipo','logo_image','site_logo','brand_logo','header_logo'] as $c) {
                if (!empty($row->{$c})) { $cand = $row->{$c}; break; }
            }
            if ($cand) {
                $p = ltrim(str_replace('\\','/',$cand), '/');
                if (Str::startsWith($p,'public/')) $p = substr($p,7);
                try { if (Storage::disk('public')->exists($p)) Storage::disk('public')->delete($p); } catch (\Throwable $e) {}
            }
        }

        if (Schema::hasColumn($t,'id')) {
            DB::table($t)->where('id',1)->update($payload);
        } else {
            DB::table($t)->limit(1)->update($payload);
        }

        return back()->with('ok', 'Logo removida com sucesso.');
    }

    /**
     * HTML mínimo de fallback (caso a view não exista).
     * (Sem <form> aninhado; inclui campos de LOGO.)
     */
    private function fallbackHtml(Request $request, array $site): string
    {
        $csrf   = csrf_token();
        $action = route('admin.config.site.update');

        $brand        = e($site['brand'] ?? '');
        $primary      = e($site['primary'] ?? '#0f172a');
        $accent       = e($site['accent'] ?? '#111827');
        $banner_title = e($site['banner_title'] ?? '');
        $banner_sub   = e($site['banner_sub'] ?? '');
        $banner_url   = e($site['banner_url'] ?? '');
        $logo_url     = e($site['logo_url'] ?? '');

        $delBannerAction = route('admin.config.site.banner.destroy');
        $delLogoAction   = route('admin.config.site.logo.destroy');

        return <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurações do Site (Fallback)</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Helvetica,Arial,sans-serif;background:#f8fafc;margin:0;padding:24px;color:#0f172a}
  .wrap{max-width:920px;margin:0 auto}
  .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 18px 10px}
  h1{font-size:20px;margin:0 0 10px}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .row-1{display:grid;grid-template-columns:1fr;gap:12px}
  label{font-weight:600;font-size:14px;margin:10px 0 4px;display:block}
  input[type=text],input[type=url]{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px}
  input[type=file]{display:block;margin-top:6px}
  .muted{color:#6b7280;font-size:13px}
  .actions{margin-top:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;text-decoration:none;cursor:pointer}
  .btn.primary{background:#111827;color:#fff;border-color:#111827}
  .btn.danger{background:#fee2e2;border-color:#fecaca}
</style>
</head>
<body>
  <div class="wrap">
    <h1>Configurações do Site</h1>
    <p class="muted">Tela simples de fallback. Crie a view <code>resources/views/admin/config/site.blade.php</code> para a UI completa.</p>

    <form class="card" method="POST" action="{$action}" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{$csrf}">
      <input type="hidden" name="_method" value="PUT">

      <div class="row">
        <div>
          <label for="brand">Nome/Marca</label>
          <input id="brand" name="brand" type="text" value="{$brand}">
        </div>
        <div>
          <label for="banner_title">Título do Banner</label>
          <input id="banner_title" name="banner_title" type="text" value="{$banner_title}">
        </div>
      </div>

      <div class="row">
        <div>
          <label for="primary">Cor Primária (hex)</label>
          <input id="primary" name="primary" type="text" value="{$primary}">
        </div>
        <div>
          <label for="accent">Cor Secundária/Accent (hex)</label>
          <input id="accent" name="accent" type="text" value="{$accent}">
        </div>
      </div>

      <div class="row-1">
        <div>
          <label for="banner_sub">Subtítulo do Banner</label>
          <input id="banner_sub" name="banner_sub" type="text" value="{$banner_sub}">
        </div>
      </div>

      <div class="row">
        <div>
          <label for="logo">Logo (upload)</label>
          <input id="logo" name="logo" type="file" accept="image/*">
          <div class="muted">PNG/SVG/JPG (preferir fundo transparente).</div>
        </div>
        <div>
          <label for="logo_url">Logo (URL)</label>
          <input id="logo_url" name="logo_url" type="url" value="{$logo_url}" placeholder="https://.../logo.png">
        </div>
      </div>

      <div class="row">
        <div>
          <label for="banner">Banner (upload)</label>
          <input id="banner" name="banner" type="file" accept="image/*">
          <div class="muted">Se não enviar arquivo/URL, o banner atual é mantido.</div>
        </div>
        <div>
          <label for="banner_url">Banner (URL)</label>
          <input id="banner_url" name="banner_url" type="url" value="{$banner_url}" placeholder="https://.../banner.jpg">
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" type="submit">Salvar</button>
      </div>
    </form>

    <div class="actions" style="margin-top:10px;">
      <form method="POST" action="{$delLogoAction}" onsubmit="return confirm('Remover a logo atual?');">
        <input type="hidden" name="_token" value="{$csrf}">
        <input type="hidden" name="_method" value="DELETE">
        <button class="btn danger" type="submit">Remover logo</button>
      </form>

      <form method="POST" action="{$delBannerAction}" onsubmit="return confirm('Remover o banner atual?');">
        <input type="hidden" name="_token" value="{$csrf}">
        <input type="hidden" name="_method" value="DELETE">
        <button class="btn danger" type="submit">Remover banner</button>
      </form>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
