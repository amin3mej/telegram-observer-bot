<?php
namespace App\Bot;

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
        // Skip updates like edited message event
        if(!$update->getMessage())
            return;

        $messageType = $this->telegram->detectMessageType($update);
        $isInPrivate = $update->getMessage()->getChat()->getType() === 'private';
        $isInGroup = $update->getMessage()->getChat()->getType() === 'supergroup'; 
        // $isInGroup = $update->getMessage()->getChat()->getId() == env('BOT_GROUP_ID');

        if($isInPrivate){
            if ($messageType == 'text') {
                $this->parsePrivateText($update);
            }
        }else if($isInGroup) {
            if ($messageType == 'text') {
                $this->parseGroupText($update);
            }
        }else{
            //TODO: Leave from this group or what?
        }
    }

    public function parsePrivateText(Update $update)
    {
        $chatId = $update->getMessage()->getChat()->getId();
        $text   = $update->getMessage()->getText();

        if (starts_with($text, 'ping')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'pong',
            ]);
        } elseif (starts_with($text, 'id')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Your ID is: ' . $chatId,
            ]);
        } elseif (starts_with($text, 'help')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => file_get_contents(base_path('responses/help.md')),
            ]);
        } elseif (starts_with($text, 'link')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => file_get_contents(base_path('responses/link.md')),
            ]);
        } elseif (starts_with($text, 'rules')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => file_get_contents(base_path('responses/rules.md')),
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Incorrect command!',
            ]);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => file_get_contents(base_path('responses/help.md')),
            ]);
        }

    }

    public function parseGroupText(Update $update)
    {
        $text = $update->getMessage()->getText();

        $isReply = is_object($update->getMessage()->getReplyToMessage());
        $replyId = $isReply ? $update->getMessage()->getReplyToMessage()->getMessageId() : $update->getMessage()->getMessageId();

        if (starts_with($text, '!report')) {
            $this->telegram->deleteMessage([
                'chat_id' => $update->getMessage()->getChat()->getId(),
                'message_id'=> $update->getMessage()->getMessageId(),
            ]);
            $ids = explode(',', env('BOT_ADMIN_IDS'));
            foreach ($ids as $id){
                $this->telegram->forwardMessage([
                    'chat_id' => $id,
                    'from_chat_id' => $update->getMessage()->getChat()->getId(),
                    'message_id' => $replyId,
                ]);
            }
        } elseif (starts_with($text, '!remove')) {
            $adminIds = explode(',', env('BOT_ADMIN_IDS'));
            $fromId = $update->getMessage()->getFrom()->getId();
            if(in_array($fromId, $adminIds)){
                if($isReply)
                {
                    $this->telegram->deleteMessage([
                        'chat_id' => $update->getMessage()->getChat()->getId(),
                        'message_id'=> $replyId,
                    ]);
                }
                $this->telegram->deleteMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'message_id'=> $update->getMessage()->getMessageId(),
                ]);
            }
        } elseif (starts_with($text, '!')) {
            $command = mb_substr($text, 1);
            $command = trim($command);
            $command = strtolower($command);

            $path = base_path('responses/answers/' . $command . '.md');
            if(file_exists($path)){
                $this->telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'parse_mode' => 'markdown',
                    'reply_to_message_id' => $replyId,
                    'text' => file_get_contents($path),
                ]);
            }
        } elseif (
            strpos($text, 'ابنتو') !== false ||
            strpos($text, 'ابونتو') !== false ||
            strpos($text, 'اوبنتو') !== false
        ) {
            if (!strpos($text, 'اوبونتو') !== false) {
             $this->telegram->sendMessage([
                 'chat_id' => $update->getMessage()->getChat()->getId(),
                 'reply_to_message_id' => $update->getMessage()->getMessageId(),
                 'text' => 'اوبونتو*',
               ]); }
        }
    }

    public function parseNewMember(Update $update)
    {
        //TODO: check for new user is a spammer bot or not.
    }
}
