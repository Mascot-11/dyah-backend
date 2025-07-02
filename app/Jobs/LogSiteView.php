<?php

namespace App\Jobs;

use App\Models\SiteView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // <-- add this import

class LogSiteView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ip, $agent, $path;

    /**
     * Create a new job instance.
     */
    public function __construct($ip, $agent, $path = null)
    {
        $this->ip = $ip;
        $this->agent = $agent;
        $this->path = $path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $location = null;

        try {
            // Replace YOUR_TOKEN with your actual token from ipinfo.io or similar API
            $response = Http::get("https://ipinfo.io/{$this->ip}/json?token=f6df956b32e5a4");

            if ($response->ok()) {
                $location = $response->json();
            }
        } catch (\Exception $e) {
            Log::error('IP Geolocation API error: ' . $e->getMessage());
        }

        SiteView::create([
            'ip_address' => $this->ip,
            'user_agent' => $this->agent,
            'view_date' => now()->toDateString(),
            'path' => $this->path,
            'location' => $location ? json_encode($location) : null,
        ]);
    }
}
