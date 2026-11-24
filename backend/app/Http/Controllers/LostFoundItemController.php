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

    public function matches(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:160'],
        ]);

        $queryText = $this->normaliseText(implode(' ', [
            $data['title'], $data['category'], $data['description'], $data['location'] ?? '',
        ]));
        $queryTokens = $this->tokens($queryText);

        $matches = LostFoundItem::query()
            ->where('status', 'open')
            ->where('kind', 'found')
            ->latest('occurred_at')
            ->get()
            ->map(function (LostFoundItem $item) use ($data, $queryTokens) {
                $itemText = $this->normaliseText(implode(' ', [
                    $item->title, $item->category, $item->description, $item->location,
                ]));
                $itemTokens = $this->tokens($itemText);
                $sharedTokens = array_values(array_intersect($queryTokens, $itemTokens));
                $score = count($queryTokens) > 0
                    ? (int) round((count($sharedTokens) / count($queryTokens)) * 70)
                    : 0;
                $reasons = [];

                if (strcasecmp($data['category'], $item->category) === 0) {
                    $score += 20;
                    $reasons[] = 'same category';
                }

                if (($data['location'] ?? '') !== '' && $this->locationOverlaps($data['location'] ?? '', $item->location)) {
                    $score += 10;
                    $reasons[] = 'similar location';
                }

                if ($sharedTokens !== []) {
                    $reasons[] = 'shared description words: ' . implode(', ', array_slice($sharedTokens, 0, 4));
                }

                return [
                    'item' => $this->publicItem($item),
                    'score' => min($score, 100),
                    'reasons' => $reasons,
                ];
            })
            ->filter(fn (array $match) => $match['score'] >= 20)
            ->sortByDesc('score')
            ->take(5)
            ->values();

        return response()->json([
            'data' => $matches,
            'algorithm' => 'Local NLP similarity: category, location, and description-token overlap',
        ]);
    }

    private function publicItem(LostFoundItem $item): array
    {
        return [
            'id' => $item->id, 'name' => $item->title, 'category' => $item->category,
            'location' => $item->location, 'date' => optional($item->occurred_at)->format('M d, Y'),
            'description' => $item->description, 'status' => $item->status,
            'tone' => $item->tone, 'icon' => $item->icon,
        ];
    }

    private function normaliseText(string $value): string
    {
        return preg_replace('/[^a-z0-9 ]+/i', ' ', strtolower($value)) ?? '';
    }

    private function tokens(string $value): array
    {
        $stopWords = ['the', 'and', 'with', 'from', 'this', 'that', 'for', 'item', 'lost', 'found'];
        return collect(preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $token) => strlen($token) >= 3 && ! in_array($token, $stopWords, true))
            ->unique()->values()->all();
    }

    private function locationOverlaps(string $left, string $right): bool
    {
        $leftTokens = $this->tokens($this->normaliseText($left));
        $rightTokens = $this->tokens($this->normaliseText($right));
        return array_intersect($leftTokens, $rightTokens) !== [];
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
