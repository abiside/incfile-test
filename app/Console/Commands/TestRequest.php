<?php

namespace App\Console\Commands;

use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Arr;
use GuzzleHttp\Exception\ConnectException;

class TestRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:test {requests=1} {--testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a testing post request';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = config('services.test.url');
        $totalRequests = $this->argument('requests');
        $requestsToAttempt = [];

        // I create an array to handle all the request attempts
        for ($i = 0; $i <= $totalRequests - 1; $i++) {
            Arr::set($requestsToAttempt, $i, false);
        }

        $completedRequests = 0;

        // The process is gonna be running until the last request is sent properly
        do {
            //dd($requestsToAttempt);
            $notSent = collect($requestsToAttempt);

            // We use a pool to send all the listed requests at the same time
            $responses = Http::pool(fn (Pool $pool) => [
                $notSent->map(function($req) use ($pool, $url) {
                    $pool->post($url);
                })
            ]);

            $completed = 0;
            $totalAttempts = count($requestsToAttempt);

            dump('Total requests: ' . $totalAttempts);

            // catch the responses 
            foreach ($responses as $key => $response) {
                // Simulate success response with a random number
                if ($response->ok() || rand(0,9) < 9) {
                    unset($requestsToAttempt[$key]);
                    $completed++;
                    $completedRequests++;
                }
            }

            dump('Sent: ' . $completed);
            dump('To Retry: ' . $totalAttempts - $completed);
            dump('------------');

        } while ($completedRequests < $totalRequests);
    }
}
