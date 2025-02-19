<?php
namespace App\Module\Model\Settings;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\Settings\SettingsDTO;

class SettingsMapper {
    public function __construct(
    ) {}

    public function map(ActiveRow $row): SettingsDTO {
        if (is_numeric($row->value)) {
            return new SettingsDTO($row->id, $row->param, (int) $row->value);
        }
        return new SettingsDTO($row->id, $row->param, $row->value);
    }
}