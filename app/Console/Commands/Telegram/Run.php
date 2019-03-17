<?php declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Models\Bot;
use App\Services\Bots\Telegram\TelegramBot;
use Illuminate\Console\Command;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class Run extends Command
{
    public const INTERVAL_BETWEEN_RUNS = 5; // in seconds
    protected $signature = 'telegram:run';
    protected $description = 'Run handler for telegram bots.';

    /** @var LoopInterface */
    private $loop;

    public function handle(): void
    {
        $this->loop = Factory::create();
        foreach (Bot::with('chats')->get() as $bot) {
            $this->botRun(new TelegramBot($this->loop, $bot));
        }
        $this->loop->run();
    }

    protected function botRun(TelegramBot $bot): void
    {
        $intervalCall = function () use ($bot) {
            $this->loop->addTimer(self::INTERVAL_BETWEEN_RUNS, function () use ($bot) {
                $this->botRun($bot);
            });
        };
        $bot->run()->then($intervalCall, $intervalCall);
    }
}