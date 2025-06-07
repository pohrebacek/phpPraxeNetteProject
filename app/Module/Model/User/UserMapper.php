<?php
namespace App\Module\Model\User;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\User\UserDTO;

class UserMapper {
    public function __construct(
    ) {}

    public function map(ActiveRow $row): UserDTO {
        bdump($row);
        return new UserDTO($row->id, $row->username, $row->email, $row->password, $row->role, $row->last_logged_in, $row->registered_at, $row->premium_until);
    }
}