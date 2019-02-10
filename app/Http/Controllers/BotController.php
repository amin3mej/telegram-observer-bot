<?php

namespace App\Http\Controllers;

use App\Bot\BotHelper;
use App\Bot\Api as Telegram;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function index($getMe = false)
    {
        $telegram = new Telegram(env('BOT_TOKEN'));
        $helper = new BotHelper($telegram);

        if($getMe){
            dd($telegram->getMe());
        }

        $tempFile = storage_path('latestProcessedId.txt');

        $id = 0;
        if(file_exists($tempFile))
            $id = file_get_contents($tempFile);

        $updates = $telegram->getUpdates(['offset' => $id + 1]);

        foreach ($updates as $update) {
            $id = $update->getUpdateId();
            $helper->parse($update);
        }

        file_put_contents($tempFile, $id);
        return 'Done';
    }

    public function webhook()
    {
        $telegram = new Telegram(env('BOT_TOKEN'));
        $helper = new BotHelper($telegram);

        $update = $telegram->getWebhookUpdates();
        $helper->parse($update);
    }

    public function setWebhook(Request $request)
    {
        $password = $request->get('password');
        if($password != env('BOT_WEBHOOK_PWS'))
            return 'Access Denied!';

        $telegram = new Telegram(env('BOT_TOKEN'));
        $route = route('webhook');
        $route = $request->get('route', $route);

        $result = $telegram->setWebhook(['url' => $route]);

        return '<pre>' . print_r($result, true) . '</pre>';
    }

    public function removeWebhook(Request $request)
    {
        $password = $request->get('password');
        if($password != env('BOT_WEBHOOK_PWS'))
            return 'Access Denied!';

        $telegram = new Telegram(env('BOT_TOKEN'));
        $result = $telegram->removeWebhook();

        return '<pre>' . print_r($result, true) . '</pre>';
    }
}
