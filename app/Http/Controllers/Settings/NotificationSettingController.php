<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingController extends Controller
{
    /**
     * Exibir página de configurações de notificação.
     */
    public function edit(Request $request): Response
    {
        $setting = $request->user()->getOrCreateNotificationSetting();

        return Inertia::render('settings/Notifications', [
            'setting' => $setting,
        ]);
    }

    /**
     * Atualizar configurações de notificação.
     */
    public function update(UpdateNotificationSettingRequest $request)
    {
        $setting = $request->user()->getOrCreateNotificationSetting();

        $setting->update($request->validated());

        return response()->json(['success' => 'Configurações de notificação atualizadas.']);
    }
}
