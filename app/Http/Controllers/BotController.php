<?php

namespace App\Http\Controllers;

use App\Bot\BotHelper;
use Telegram\Bot\Api as Telegram;

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
}
