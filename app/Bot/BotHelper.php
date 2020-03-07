<?php
namespace App\Bot;

use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Objects\Update;

/**
 * Class BotHelper
 * @package App\Bot
 *
 * @property Api $telegram
 */
class BotHelper {

    private $telegram;
    private $messageToRemove = null;

    public function __construct($telegram)
    {
        $this->telegram = $telegram;
    }

    public function __destruct()
    {
        if ($this->messageToRemove)
        {
            try {
                $this->telegram->deleteMessage([
                    'chat_id' => $this->messageToRemove->getChat()->getId(),
                    'message_id' => $this->messageToRemove->getMessageId(),
                ]);
            } catch (\Exception $exception) {

            }
        }
    }

    public function parse(Update $update)
    {
        // Skip updates like edited message event
        if(!$update->getMessage())
            return;

        $messageType = $this->telegram->detectMessageType($update);
        $isInPrivate = $update->getMessage()->getChat()->getType() === 'private';
        $isInGroup = $update->getMessage()->getChat()->getType() === 'supergroup'; 

        if($isInPrivate){
            if ($messageType == 'text') {
                $this->parsePrivateText($update);
            }
        }else if($isInGroup) {
            if ($messageType == 'text') {
                $this->parseGroupText($update);
            } elseif ($messageType == 'document') {
                $this->parseGroupAttachment($update);
            } else {
                $this->parseGroupEvents($update);
            }
        }else{
            //TODO: Leave from this group or what?
        }
    }

    public function parsePrivateText(Update $update)
    {
        $chatId = $update->getMessage()->getChat()->getId();
        $text   = str_replace('‌', '', $update->getMessage()->getText());

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
        } elseif (starts_with($text, 'help') || starts_with($text, '/start')) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => file_get_contents(base_path('responses/help.md')),
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
        $text = $this->autoRemoveChecker($update);
        $isReply = is_object($update->getMessage()->getReplyToMessage());
        $replyId = $isReply ? $update->getMessage()->getReplyToMessage()->getMessageId() : $update->getMessage()->getMessageId();

        if($update->getMessage()->getFrom()->getIsBot()){
            $this->telegram->deleteMessage([
                'chat_id' => $update->getMessage()->getChat()->getId(),
                'message_id' => $update->getMessage()->getMessageId(),
            ]);
        }

        if (starts_with($text, '!report')) {
            $fromChatId = $update->getMessage()->getChat()->getId();
            $adminIds = $this->getAdminIdList($fromChatId);
            foreach ($adminIds as $id) {
                try {
                    $this->telegram->forwardMessage([
                        'chat_id' => $id,
                        'from_chat_id' => $update->getMessage()->getChat()->getId(),
                        'message_id' => $update->getMessage()->getMessageId(),
                    ]);
                    $this->telegram->forwardMessage([
                        'chat_id' => $id,
                        'from_chat_id' => $update->getMessage()->getChat()->getId(),
                        'message_id' => $replyId,
                    ]);
                }catch (\Exception $exception){

                }
            }
            $this->telegram->deleteMessage([
                'chat_id' => $update->getMessage()->getChat()->getId(),
                'message_id' => $update->getMessage()->getMessageId(),
            ]);
        } elseif (starts_with($text, '!link')) {
            $link = Cache::remember('joinLinkFor' . $update->getMessage()->getChat()->getId(), 180, function () use ($update) {
                $link = $this->telegram->exportChatInviteLink([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                ]);
                if ($link['ok']) {
                    return $link['result'];
                }
                return null;
            });

            if ($link) {
                $this->telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $replyId,
                    'text' => sprintf(file_get_contents(base_path('responses/link.md')), $link),
                ]);
            }
        } elseif (starts_with($text, '!remove')) {
            if($this->isAdmin($update)){
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
        }  elseif (starts_with($text, '!trjob')) {
            if (!$isReply) return;
            $replySender = $update->getMessage()->getReplyToMessage()->getFrom();
            if($this->isAdmin($update)){
                $this->telegram->deleteMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'message_id'=> $replyId,
                ]);
                $username = '[' . $replySender->getFirstName() . ' ' . $replySender->getLastName() . '](tg://user?id=' . $replySender->getId() . ')';
                $path = base_path('responses/answers/trjob.md');
                $text = file_get_contents($path);
                $text = str_replace('{username}', $username, $text);
                $this->telegram->sendMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'parse_mode' => 'markdown',
                    'text' => $text,
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
            } else {
                $this->messageToRemove = null;
            }
        } elseif (
            strpos($text, 'اوبونتو') === false &&
            (
                strpos($text, 'ابنتو') !== false ||
                strpos($text, 'ابونتو') !== false ||
                strpos($text, 'اوبنتو') !== false
            )
        ) {
            $this->telegram->sendMessage([
                'chat_id' => $update->getMessage()->getChat()->getId(),
                'reply_to_message_id' => $update->getMessage()->getMessageId(),
                'text' => 'اوبونتو*',
            ]);
        }
    }
    public function parseGroupAttachment(Update $update)
    {
        $filename = $update->getMessage()->getDocument()->getFileName();
        
        if (substr($filename, -4) === '.apk' && !$this->isAdmin($update)) {
            try{
                $this->telegram->deleteMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'message_id' => $update->getMessage()->getMessageId(),
                ]);
            }catch (\Exception $exception){
                // Maybe message was deleted before any action.
            }
            try {
                $this->telegram->kickChatMember([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'user_id' => $update->getMessage()->getFrom()->getId(),
                ]);
            }catch (\Exception $exception){
                // Maybe the user did left or had admin permission.
            }
        }
    }

    public function parseGroupEvents(Update $update)
    {
        if(isset($update->getMessage()['left_chat_member'])){
            //If i kick a person, delete the message.
            if(isset($update->getMessage()['from']['username']) && $update->getMessage()['from']['username'] == env('BOT_USERNAME')){
                try {
                    $this->telegram->deleteMessage([
                        'chat_id' => $update->getMessage()->getChat()->getId(),
                        'message_id' => $update->getMessage()->getMessageId(),
                    ]);
                }catch (\Exception $exception){
                    // Maybe this user is left or have admin permission.
                }
            }
        }
        //Check if new chat member added
        if(isset($update->getMessage()['new_chat_members'])) {
            $newChatMembers = $update->getMessage()['new_chat_members'];

            if(count($newChatMembers) == 1 && $update->getMessage()->getFrom()->getId() == $newChatMembers[0]['id']){
                // Username joined the group.
                $this->telegram->deleteMessage([
                    'chat_id' => $update->getMessage()->getChat()->getId(),
                    'message_id' => $update->getMessage()->getMessageId(),
                ]);
            }

            /** @var boolean $flag is there any bot added in this update? */
            $flag = false;
            foreach ($newChatMembers as $newChatMember) {
                if ($newChatMember['is_bot']) {
                    $flag = true;
                    break;
                }
            }

            if ($flag) {
                try {
                    $this->telegram->kickChatMember([
                        'chat_id' => $update->getMessage()->getChat()->getId(),
                        'user_id' => $update->getMessage()->getFrom()->getId(),
                    ]);
                }catch (\Exception $exception){
                    // Maybe this user is left or have admin permission.
                }
                foreach ($newChatMembers as $newChatMember) {
                    $this->telegram->kickChatMember([
                        'chat_id' => $update->getMessage()->getChat()->getId(),
                        'user_id' => $newChatMember['id'],
                    ]);
                }
            }
        }
    }

    private function autoRemoveChecker(Update $update)
    {
        $text = $update->getMessage()->getText();
        if (starts_with($text, '!!')) {
            $this->messageToRemove = $update->getMessage();
            return mb_substr($text, 1);
        }
        return $text;
    }

    private function getAdminIdList($groupID){
        return Cache::remember('admin-list-' . $groupID, 24 * 60 * 60, function () use ($groupID) {
            $result = $this->telegram->getChatAdministrators([
                'chat_id' => $groupID,
            ]);
            return collect($result['result'])->pluck('user.id')->all();
        });

        return [];
    }

    private function isAdmin(Update $update) {
        $fromId = $update->getMessage()->getFrom()->getId();
        return in_array($fromId, $this->getAdminIdList($update->getMessage()->getChat()->getId()));
    }
}
