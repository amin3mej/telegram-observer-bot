<?php
namespace App\Bot;

class Api extends \Telegram\Bot\Api
{
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
        $response = $this->post('deleteMessage', $params);

        return $response->getDecodedBody();
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
        $response = $this->post('kickChatMember', $params);

        return $response->getDecodedBody();
    }

    public function exportChatInviteLink(array $params)
    {
        $response = $this->post('exportChatInviteLink', $params);

        return $response->getDecodedBody();
    }

    public function restrictChatMember(array $params)
    {
        $response = $this->post('restrictChatMember', $params);

        return $response->getDecodedBody();
    }

    public function getChatAdministrators(array $params)
    {
        $response = $this->post('getChatAdministrators', $params);

        return $response->getDecodedBody();
    }

}