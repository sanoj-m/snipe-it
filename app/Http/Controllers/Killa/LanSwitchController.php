<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * [killa lan-switch] KCP-062 — one-time signed session handoff to the LAN URL.
 *
 * redirectToLan() (authed, canonical host) mints a 60-second relative signed
 * URL for killa.lan-handoff and redirects to it on KILLA_LAN_URL. The handoff
 * endpoint validates the relative signature (host-independent), logs the user
 * in, and redirects to the original path. Same APP_KEY on both hostnames, so
 * no shared infrastructure is needed.
 */
class LanSwitchController extends Controller
{
    public function redirectToLan(Request $request)
    {
        $lanUrl = rtrim((string) config('app.lan_url'), '/');
        abort_if($lanUrl === '', 404);

        $handoff = URL::temporarySignedRoute('killa.lan-handoff', now()->addSeconds(60), [
            'user' => $request->user()->id,
            'to' => $this->safePath($request->query('to')),
        ], false);

        return redirect()->away($lanUrl.$handoff);
    }

    public function handoff(Request $request)
    {
        abort_unless($request->hasValidRelativeSignature(), 403);

        $userId = (int) $request->query('user');
        abort_unless($userId && User::where('id', $userId)->where('activated', 1)->exists(), 403);

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect($this->safePath($request->query('to')));
    }

    /**
     * Path-only guard against open redirects: must start with a single slash.
     */
    private function safePath(?string $to): string
    {
        $to = '/'.ltrim((string) $to, '/');

        return preg_match('#^/([^/]|$)#', $to) === 1 ? $to : '/';
    }
}
