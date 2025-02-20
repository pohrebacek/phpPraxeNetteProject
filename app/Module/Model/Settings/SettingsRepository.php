<?php
namespace App\Module\Model\Settings;

use Nette;
use App\Module\Model\Base\BaseRepository;
use App\Module\Model\Settings\SettingsMapper;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;

final class SettingsRepository extends BaseRepository
{
	public function __construct(
		protected Nette\Database\Explorer $database,
		public SettingsMapper $settingsMapper
	) {
		$this->table = "settings";
	}

	public function saveSettings($data): void
	{
		foreach($data as $key => $value) {
			bdump($key);
			$this->database->table($this->table)->where("param", $key)->update(["value" => $value]);	//uloží hodnotu, to je totiž jediný co se může měnit
		}
	}

	public function getRowByKey($key): ActiveRow|null
	{
		return $this->database->table($this->table)->where("param", $key)->fetch();
	}

	
}
