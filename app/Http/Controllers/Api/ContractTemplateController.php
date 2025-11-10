<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractTemplate::query();
        $query->where(function ($q) {
            $q->where('is_public', true)
              ->orWhere('user_id', auth()->id())
              ->orWhere('organization_id', auth()->user()->organization_id ?? null);
        });

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'usage_count');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $templates = $query->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $templates]);
    }

    public function categories()
    {
        $categories = ContractTemplate::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function show($id)
    {
        $template = ContractTemplate::findOrFail($id);

        if (!$template->is_public && $template->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_public' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $template = ContractTemplate::create(array_merge($request->all(), ['user_id' => auth()->id()]));

        return response()->json(['success' => true, 'message' => 'Template created', 'data' => $template], 201);
    }

    public function update(Request $request, $id)
    {
        $template = ContractTemplate::findOrFail($id);

        if ($template->is_system || ($template->user_id !== auth()->id() && !auth()->user()->hasRole('admin'))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template->update($request->all());

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function destroy($id)
    {
        $template = ContractTemplate::findOrFail($id);

        if ($template->is_system) {
            return response()->json(['success' => false, 'message' => 'Cannot delete system templates'], 422);
        }

        if ($template->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template deleted']);
    }

    public function popular()
    {
        $templates = ContractTemplate::where('is_public', true)
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }
}
