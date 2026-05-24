<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\XmlSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FetchXmlSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $sourceId) {}

    public function handle(): void
    {
        /** @var XmlSource|null $source */
        $source = XmlSource::withoutGlobalScopes()->find($this->sourceId);
        if (! $source || ! $source->is_active) return;

        $response = Http::timeout(10)->get($source->url);
        if (! $response->successful()) return;

        $value = $this->extractValue($response->body(), $source->selector);
        if ($value === null) {
            $source->update(['last_fetched_at' => now()]);
            return;
        }

        $content = str_replace('{value}', $value, $source->template);

        // Determine target social account
        $accountId = $source->social_account_id;
        if (! $accountId) {
            $account = SocialAccount::withoutGlobalScopes()
                ->where('organization_id', $source->organization_id)
                ->where('provider', 'x')
                ->where('is_active', true)
                ->first();
            $accountId = $account?->id;
        }

        if (! $accountId) {
            $source->update(['last_fetched_at' => now()]);
            return;
        }

        Post::create([
            'organization_id' => $source->organization_id,
            'social_account_id' => $accountId,
            'content' => $content,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        $source->update(['last_fetched_at' => now()]);
    }

    private function extractValue(string $xml, string $selector): ?string
    {
        try {
            $doc = new \SimpleXMLElement($xml);
        } catch (\Throwable $e) {
            return null;
        }

        if (str_starts_with($selector, '/')) {
            $result = $doc->xpath($selector);
            return $result ? (string) $result[0] : null;
        }

        $cur = $doc;
        foreach (explode('/', $selector) as $node) {
            if (isset($cur->$node)) {
                $cur = $cur->$node;
            } else {
                return null;
            }
        }
        return (string) $cur;
    }
}
