<?php

namespace App\Http\Controllers;

use App\Models\LostFoundItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LostFoundItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LostFoundItem::query()->where('status', 'open')->latest('occurred_at');

        if ($request->filled('category') && $request->string('category')->lower()->toString() !== 'all items') {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('q')) {
            $term = '%' . $request->string('q')->toString() . '%';
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', $term)
                ->orWhere('category', 'like', $term)
                ->orWhere('location', 'like', $term)
                ->orWhere('description', 'like', $term));
        }

        $items = $query->paginate($request->integer('per_page', 12));
        $user = $request->user();
        $savedIds = $user ? $user->savedItems()->pluck('lost_found_items.id')->all() : [];

        return response()->json([
            'data' => collect($items->items())->map(fn (LostFoundItem $item) => [
                'id' => $item->id,
                'name' => $item->title,
                'category' => $item->category,
                'location' => $item->location,
                'date' => optional($item->occurred_at)->format('M d, Y'),
                'age' => optional($item->occurred_at)->diffForHumans(),
                'status' => $item->occurred_at?->isAfter(now()->subDay()) ? 'Recently found' : 'Found',
                'description' => $item->description,
                'tone' => $item->tone,
                'icon' => $item->icon,
                'saved' => in_array($item->id, $savedIds, true),
            ]),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'kind' => ['required', 'in:found,lost'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:2000'],
            'location' => ['required', 'string', 'max:160'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $item = $request->user()->reportedItems()->create([
            ...$data,
            'tone' => ['blue', 'gold', 'rose', 'mint', 'ink', 'green'][random_int(0, 5)],
            'icon' => '◌',
        ]);

        return response()->json(['item' => $item], 201);
    }

    public function save(Request $request, LostFoundItem $item): JsonResponse
    {
        $request->user()->savedItems()->syncWithoutDetaching([$item->id]);
        return response()->json(['saved' => true]);
    }

    public function unsave(Request $request, LostFoundItem $item): JsonResponse
    {
        $request->user()->savedItems()->detach($item->id);
        return response()->json(['saved' => false]);
    }

    public function saved(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->savedItems()->where('status', 'open')->latest('saved_items.created_at')->get()]);
    }
}
