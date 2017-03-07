<?php

namespace Library\SN;

require_once 'vkapi.class.php';

interface SNInterface
{
    /**
     *
     * @param array $socialNetworkUids
     * @return array
     */
    public function getUsers($socialNetworkUids);
    public function isGroupMember($socialNetworkUid, $socialNetworkGroupId);
    public function setUserLevel($socialNetworkUid, $socialNetworkLevel);

    /**
     *
     * @param array $socialNetworkUid
     * @param string $message
     * @return boolean
     */

    public function sendNotification($socialNetworkUid, $message);
    public function getJavaScript();
    public function transactionChek($transaction_id);
    public function getFriendCount();
    public function getFriends();
    public function getFriendsApp();
    public function getFriendsOnline();
    public function transactionCreate($price, $serviceId);
    public function addActivity($text);
    public function isBirthDay($date);
    public function getSocialObject();
    public function userSync($socialNetworkUid, $socialNetworkLevel, $socialNetworkXp);
    public function check_in_another_game($socialNetworkUid);
    public function setCounters($usersAndCounters);

    /**
     *
     * @param $socialNetworkUid
     * @param string $country (RU,UA)
     * @param string $age_range (18-25)
     * @param string $gender (0 / 1 / 2) 1-f, 2-m
     *
     * @return boolean
     */

    public function check_targeting($socialNetworkUid, $country, $age_range, $gender, $bdate);

    /**
     * for check connection with SN
     * @return ok
     */

    public function check_connection();

}