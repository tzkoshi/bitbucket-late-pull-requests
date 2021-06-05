<?php

use Commando\Command;
use Httpful\Request;

include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$cmd = new Command();

$cmd->option('base')
    ->required();
$cmd->option('token')
    ->required();


$result = Request::get(trim($cmd['base'], '/') . '/' . 'rest/api/1.0/inbox/pull-requests')
    ->addHeader('Authorization', sprintf('Bearer %s', $cmd['token']))
    ->expectsJson()
    ->send();


$pullRequests = $result->body->values;
$formatted = [];
print '_Висящи PR:_' . PHP_EOL;
$groups = [];
foreach ($pullRequests as $pullRequest) {
    $author = $pullRequest->author->user->name;
    foreach ($pullRequest->reviewers as $reviewer) {
        $groups[$reviewer->user->name][] = $pullRequest;
    }
}

$now = time();
foreach ($groups as $author => $pullRequests) {
    print sprintf('%s:', $author) . PHP_EOL;
    foreach ($pullRequests as $pullRequest) {
        $link = $pullRequest->links->self[0]->href;
        $status = $pullRequest->state;
        $daysDiff = number_format(abs(($pullRequest->updatedDate / 1000) - $now) / (60 * 60 * 24), 1);
        print sprintf('* [**%s**](%s) (%s дни) - %s', $pullRequest->title, $link, $daysDiff, $status) . PHP_EOL;
    }
    print PHP_EOL . PHP_EOL;
}
