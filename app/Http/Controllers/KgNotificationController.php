<?php

namespace App\Http\Controllers;

use App\Models\KgNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KgNotificationController extends Controller
{
    /** Mark one notification read and open the exact page it points to. */
    public function go($id)
    {
        $n = KgNotification::where('user_id', Auth::id())->findOrFail($id);
        if (!$n->read_at) {
            $n->read_at = now();
            $n->save();
        }
        $url = $n->url ?: url('/dashboard');
        // only ever send people to a page of this application
        if (preg_match('#^https?://#i', $url) && !str_starts_with($url, url('/'))) {
            $url = url('/dashboard');
        }
        return redirect($url);
    }

    public function readAll(Request $request)
    {
        KgNotification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
        return redirect()->back();
    }

    public function index()
    {
        $items = KgNotification::where('user_id', Auth::id())->orderByDesc('id')->limit(200)->get();
        return view('backend.notification.kg_index', compact('items'));
    }
}
