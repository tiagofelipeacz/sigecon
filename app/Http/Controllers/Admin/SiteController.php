<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    public function edit()
    {
        $site = SiteSetting::query()->first();
        if (!$site) {
            $site = new SiteSetting(); // preenche com defaults do model/migration
        }
        return view('admin.site.edit', compact('site'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'brand'         => ['required','string','max:100'],
            'primary'       => ['required','string','size:7'],  // tipo #RRGGBB
            'accent'        => ['required','string','size:7'],
            'banner_title'  => ['required','string','max:120'],
            'banner_sub'    => ['required','string','max:255'],
            'banner_image'  => ['nullable','image','max:2048'], // 2MB
        ]);

        $site = SiteSetting::query()->first() ?? new SiteSetting();

        // upload opcional
        if ($request->hasFile('banner_image')) {
            // apaga anterior (se quiser)
            if ($site->banner_image && Storage::disk('public')->exists($site->banner_image)) {
                Storage::disk('public')->delete($site->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('banners', 'public');
        }

        $site->fill($data)->save();

        return redirect()->route('admin.site.edit')->with('ok', 'Configurações atualizadas!');
    }
}
