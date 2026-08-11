<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Picks the best expense category for a free-text expense.
 *
 * "Bought ink at Office Depot" → Office. Uses Claude (cheap model) to classify
 * against the workspace's existing categories, creating a sensible new one when
 * nothing fits. Falls back to a keyword map when the API isn't configured, so
 * every expense still gets categorized.
 */
class ExpenseCategorizer
{
    /** A small palette to colour freshly-created categories. */
    private const PALETTE = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#ec4899', '#14b8a6'];

    /** Keyword → canonical category, used for the no-API fallback. */
    private const KEYWORDS = [
        'Office' => ['office', 'depot', 'staples', 'ink', 'printer', 'paper', 'stationery', 'desk', 'chair'],
        'Software' => ['software', 'saas', 'subscription', 'license', 'github', 'adobe', 'figma', 'slack', 'zoom', 'aws', 'hosting', 'domain'],
        'Travel' => ['flight', 'hotel', 'uber', 'lyft', 'taxi', 'airbnb', 'train', 'mileage', 'parking', 'airline'],
        'Meals' => ['restaurant', 'coffee', 'lunch', 'dinner', 'starbucks', 'food', 'catering', 'meal'],
        'Marketing' => ['ads', 'advertising', 'facebook', 'google ads', 'marketing', 'campaign', 'seo', 'mailchimp'],
        'Utilities' => ['electric', 'water', 'gas', 'internet', 'phone', 'utility', 'comcast', 'verizon'],
        'Rent' => ['rent', 'lease', 'wework', 'coworking'],
        'Equipment' => ['laptop', 'computer', 'monitor', 'hardware', 'camera', 'equipment'],
        'Professional' => ['legal', 'lawyer', 'accountant', 'consultant', 'accounting', 'attorney'],
        'Supplies' => ['supplies', 'materials', 'inventory'],
    ];

    public function __construct(private ClaudeClient $claude) {}

    /**
     * Resolve a category for the given expense details, creating one if needed.
     */
    public function categorize(string $description, ?string $vendor = null, ?float $amount = null): ?ExpenseCategory
    {
        $existing = ExpenseCategory::orderBy('name')->get();

        $name = $this->claude->enabled()
            ? $this->classifyWithClaude($description, $vendor, $amount, $existing)
            : null;

        $name ??= $this->classifyWithKeywords($description, $vendor);

        if ($name === null) {
            return null;
        }

        return $this->resolveCategory($name, $existing);
    }

    /**
     * @param  Collection<int,ExpenseCategory>  $existing
     */
    private function classifyWithClaude(string $description, ?string $vendor, ?float $amount, $existing): ?string
    {
        $names = $existing->pluck('name')->all();
        $list = $names === [] ? '(none yet)' : implode(', ', $names);

        $system = <<<PROMPT
        You categorize small-business expenses. Choose the single best category.
        Prefer one of the workspace's EXISTING categories: {$list}.
        Only invent a new category (1-2 words, Title Case) if none of the existing
        ones reasonably fit. Respond with ONLY this JSON: {"category": "<name>"}.
        PROMPT;

        $details = trim(sprintf(
            "Description: %s\nVendor: %s\nAmount: %s",
            $description,
            $vendor ?: 'unknown',
            $amount !== null ? number_format($amount, 2) : 'unknown',
        ));

        $result = $this->claude->completeJson(
            model: $this->claude->model('categorize'),
            messages: [['role' => 'user', 'content' => $details]],
            system: $system,
            maxTokens: 64,
            timeout: 30,
        );

        $name = $result['category'] ?? null;

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    private function classifyWithKeywords(string $description, ?string $vendor): ?string
    {
        $haystack = Str::lower(trim($description.' '.($vendor ?? '')));
        if ($haystack === '') {
            return null;
        }

        foreach (self::KEYWORDS as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($haystack, $word)) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    /**
     * Match the chosen name to an existing category (case-insensitive) or create it.
     *
     * @param  Collection<int,ExpenseCategory>  $existing
     */
    private function resolveCategory(string $name, $existing): ExpenseCategory
    {
        $match = $existing->first(fn (ExpenseCategory $c) => Str::lower($c->name) === Str::lower($name));
        if ($match) {
            return $match;
        }

        return ExpenseCategory::firstOrCreate(
            ['name' => $name],
            ['color' => self::PALETTE[$existing->count() % count(self::PALETTE)]],
        );
    }
}
