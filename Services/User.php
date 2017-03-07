<?php
/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 8/25/16
 * Time: 11:58 AM
 */

namespace Game\Services;

use Game\Components;
use Library\Base\BaseService;

/**
 * Class Editor для редактора
 *
 * @package Game\Services
 */
class User extends BaseService
{
    /**
     * Удаление Юзера
     *
     * @return bool
     */
    public function delete() {
        return Components\User::getInstance()->deleteUser();
    }

    public function resetQuest() {
        return Components\User::getInstance()->resetQuest();
    }
}