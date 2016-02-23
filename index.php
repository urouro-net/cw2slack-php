<?php
date_default_timezone_set('Asia/Tokyo');
require 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required([
    'CHATWORK_TOKEN',
    'SLACK_ENDPOINT',
    'SLACK_CHANNEL',
]);

$chatworkToken = getenv('CHATWORK_TOKEN');
$slackEndpoint = getenv('SLACK_ENDPOINT');
$slackChannel = getenv('SLACK_CHANNEL');

$slackClient = new \Maknz\Slack\Client($slackEndpoint, [
    'channel' => $slackChannel,
]);
$chatworkClient = new \Polidog\Chatwork\Client($chatworkToken);

$rooms = $chatworkClient->api('rooms')->show();

foreach ($rooms as $room) {
    $messages = $chatworkClient->api('rooms')->messages($room->roomId)->show(false);

    if (count($messages) === 0) {
        // echo '[' . $room->name . '] No messages' . "\n";
        continue;
    }

    foreach ($messages as $message) {
        $sendTime = date('Y-m-d H:i:s', $message->sendTime);

        $messageUrl = 'https://chatwork.com/#!rid' . $room->roomId . '-' . $message->messageId;
        $slackClient
            ->from('CW2Slack')
            ->withIcon($message->account->avatarImageUrl)
            ->attach([
                'fallback' => $room->name . ' ' . $messageUrl,
                'title' => $room->name,
                'title_link' => $messageUrl,
                'text' => $message->body,
                'fields' => [
                    [
                        'title' => 'from',
                        'value' => $message->account->name,
                        'short' => true,
                    ],
                    [
                        'title' => 'at',
                        'value' => $sendTime,
                        'short' => true,
                    ]
                ],
                'color' => '#EEEEEE',
            ])->send();
    }
}
