<?php
namespace App\Bot;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Class BotHelper
 * @package App\Bot
 *
 * @property Api $telegram
 */
class BotHelper {

    private $telegram;

    public function __construct($telegram)
    {
        $this->telegram = $telegram;
    }

    public function parse(Update $update)
    {
        $text = $update->getMessage()->getText();
        $isInGroup = $update->getMessage()->getChat()->getId() == env('BOT_GROUP_ID');
        $isInPrivate = $update->getMessage()->getChat()->getType() === 'private';

        if($isInGroup) {
            if ($text) {
                $this->parseGroupText($update);
            }
        }else if($isInPrivate){
            //TODO: Respond to some commands like ping
        }else{
            //TODO: Leave from this group or what?
        }
    }

    public function parseGroupText(Update $update)
    {
        $text = $update->getMessage()->getText();

        if(starts_with($text, '!report')){
            //TODO: Forward message to admins
        }

        if(starts_with($text, '!faq')){
            $command = mb_substr($text, 4);
            $command = trim($command);
            $command = strtolower($command);

            $isReply = is_object($update->getMessage()->getReplyToMessage());
            $replyId = $isReply ? $update->getMessage()->getReplyToMessage()->getMessageId() : $update->getMessage()->getMessageId();

            $path = base_path('answers/' . $command . '.md');
            if(file_exists($path)){
                $this->telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'parse_mode' => 'markdown',
                    'reply_to_message_id' => $replyId,
                    'text' => file_get_contents($path),
                ]);
            }else{
                $this->telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $replyId,
                    'text' => 'Incorrect Command!',
                ]);
            }

        }
    }

    public function parseNewMember(Update $update)
    {
        //TODO: check for new user is a spammer bot or not.
    }
}