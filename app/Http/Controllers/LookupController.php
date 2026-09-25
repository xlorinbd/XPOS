<?php

namespace App\Http\Controllers;

use App\Models\Lookup;
use App\Models\Product;
use App\Services\ProcessorNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class LookupController extends Controller
{
    private function authorizeManage(): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && $role->hasPermissionTo('products-add');
    }

    private function typeOrFail(string $type): array
    {
        abort_unless(isset(Lookup::TYPES[$type]), 404);
        return Lookup::TYPES[$type];
    }

    public function index(string $type)
    {
        $meta = $this->typeOrFail($type);
        if (!$this->authorizeManage()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }

        $items = Lookup::ofType($type)->orderBy('name')->get();
        $usage = [];
        if ($type === 'charger_model') {
            $usage = Product::whereNotNull('adapter_model_id')->selectRaw('adapter_model_id, count(*) c')->groupBy('adapter_model_id')->pluck('c', 'adapter_model_id')->all();
        }

        return view('backend.lookup.index', compact('type', 'meta', 'items', 'usage'));
    }

    public function store(Request $request, string $type)
    {
        $meta = $this->typeOrFail($type);
        abort_unless($this->authorizeManage(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('lookups', 'name')->where('type', $type)],
            'code' => ['nullable', 'string', 'max:191'],
        ]);

        $name = trim($data['name']);
        if ($type === 'processor') {
            $name = ProcessorNormalizer::normalize($name)['normalized'];
            if (Lookup::ofType('processor')->where('match_key', Lookup::processorKey($name))->exists()) {
                return redirect()->back()->withInput()->with('not_permitted', 'This processor is already in the list.');
            }
        }

        Lookup::create([
            'type' => $type,
            'name' => $name,
            'code' => $meta['has_code'] ? ($data['code'] ?? null) : null,
            'is_active' => true,
        ]);

        return redirect()->route('lookups.index', $type)->with('message', $meta['singular'] . ' added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $meta = $this->typeOrFail($type);
        abort_unless($this->authorizeManage(), 403);

        $item = Lookup::ofType($type)->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('lookups', 'name')->where('type', $type)->ignore($item->id)],
            'code' => ['nullable', 'string', 'max:191'],
        ]);

        $name = trim($data['name']);
        if ($type === 'processor') {
            $name = ProcessorNormalizer::normalize($name)['normalized'];
        }

        $item->update([
            'name' => $name,
            'code' => $meta['has_code'] ? ($data['code'] ?? null) : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('lookups.index', $type)->with('message', $meta['singular'] . ' updated.');
    }

    public function destroy(string $type, int $id)
    {
        $meta = $this->typeOrFail($type);
        abort_unless($this->authorizeManage(), 403);

        $item = Lookup::ofType($type)->findOrFail($id);
        if ($type === 'charger_model' && Product::where('adapter_model_id', $item->id)->exists()) {
            return redirect()->back()->with('not_permitted', 'This charger model is linked to products. Deactivate it instead of deleting.');
        }
        $item->delete();

        return redirect()->route('lookups.index', $type)->with('message', $meta['singular'] . ' removed.');
    }

    public function serialCondition(Request $request, int $id)
    {
        $role = Role::find(Auth::user()->role_id);
        abort_unless($role && $role->hasPermissionTo('products-edit'), 403);

        $serial = \App\Models\ProductSerial::findOrFail($id);
        $text = trim((string) $request->input('detailed_condition', ''));
        $serial->detailed_condition = $text === '' ? null : $text;
        $serial->save();
        remember_condition_tags([$text]);

        return response()->json(['ok' => true, 'detailed_condition' => $serial->detailed_condition]);
    }

    public function suggest(string $type)
    {
        $this->typeOrFail($type);
        return Lookup::ofType($type)->active()->orderBy('name')->pluck('name');
    }

    public function normalizeProcessor(Request $request)
    {
        $values = (array) $request->input('values', []);
        $result = [];
        foreach ($values as $raw) {
            $result[$raw] = ProcessorNormalizer::normalize($raw);
        }
        return response()->json($result);
    }
}
