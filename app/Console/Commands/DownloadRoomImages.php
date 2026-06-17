<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadRoomImages extends Command
{
    protected $signature = 'rooms:download-images';

    protected $description = 'Download placeholder room images and store them in storage/app/public/rooms';

    /**
     * @var array<string, string>
     */
    private array $images = [
        'standard-101.jpg' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80',
        'double-202.jpg'   => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80',
        'suite-301.jpg'    => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&q=80',
        'placeholder.jpg'  => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80',
    ];

    public function handle(): int
    {
        foreach ($this->images as $filename => $url) {
            $this->info("Downloading {$filename}...");

            $response = Http::get($url);

            if (! $response->successful()) {
                $this->error("Failed to download {$filename} ({$response->status()})");
                continue;
            }

            Storage::disk('public')->put("rooms/{$filename}", $response->body());

            $this->info("Saved to storage/app/public/rooms/{$filename}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
