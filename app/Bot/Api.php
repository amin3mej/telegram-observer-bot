<?php
namespace App\Bot;

class Api extends \Telegram\Bot\Api
{
    function getDecodedResponseBody($action, array $params)
    {
        return $this->post($action, $params)->getDecodedBody();
    }
    /**
     * Send text messages.
     *
     * <code>
     * $params = [
     *   'chat_id'                  => '',
     *   'message_id'               => '',
     * ];
     * </code>
     *
     * @link https://core.telegram.org/bots/api#deletemessage
     *
     * @param array    $params
     *
     * @var int|string $params ['chat_id']
     * @var string     $params ['message_id']
     *
     * @return array
     */
    public function deleteMessage(array $params)
    {
        return getDecodedResponseBody('deleteMessage', $params);
    }


    /**
     * Kick chat member.
     *
     * <code>
     * $params = [
     *   'chat_id'                  => '',
     *   'user_id'                  => '',
     *   'until_date'               => '',
     * ];
     * </code>
     *
     * @link https://core.telegram.org/bots/api#kickChatMember
     *
     * @param array    $params
     *
     * @var int|string $params ['chat_id']
     * @var int        $params ['user_id']
     * @var int        $params ['until_date']
     *
     * @return array
     */
    public function kickChatMember(array $params)
    {
        return getDecodedResponseBody('kickChatMember', $params);
    }

    public function exportChatInviteLink(array $params)
    {
        return getDecodedResponseBody('exportChatInviteLink', $params);
    }

    public function restrictChatMember(array $params)
    {
        return getDecodedResponseBody('restrictChatMember', $params);
    }

    public function getChatAdministrators(array $params)
    {
        return getDecodedResponseBody('getChatAdministrators', $params);
    }

}