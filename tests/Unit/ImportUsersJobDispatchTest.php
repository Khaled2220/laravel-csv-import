<?php

use App\Jobs\ImportUsersJob;
use Illuminate\Support\Facades\Queue;

test('import users job is dispatched', function () {
    Queue::fake();

    $importId = 1;

    ImportUsersJob::dispatch($importId);

    Queue::assertPushed(
        ImportUsersJob::class,
        function ($job) use ($importId) {
            return $job->importId === $importId;
        }
    );
});