<?php
namespace App\Module\Model\Settings;

use App\Module\Model\Settings\SettingsMapper;
use Nette;
use App\Module\Model\Settings\SettingsRepository;

final class SettingsFacade  //facade je komplexnější práci s nějakym repository, prostě složitější akce, plus může pracovat s víc repos najednou
{
	public function __construct(
		private SettingsRepository $settingsRepository,
        protected Nette\Database\Explorer $database,
        private SettingsMapper $settingsMapper
	) {
	}


    public function getSettingsDTO(int $id): SettingsDTO    //jedna funkce co za tebe převede row na DTO aniž bys to v kodu musel vypisovat jak kokot 
    {
        $postRow = $this->settingsRepository->getRowById($id);
        return $this->settingsMapper->map($postRow);
    }

    public function allSetingsToDTO()
    {
        $settingsParam = [];
        $settings = $this->settingsRepository->getAll();
        foreach ($settings as $setting) {
            $settingDTO = $this->getSettingsDTO($setting->id);
            $settingsParam[$settingDTO->param] = $settingDTO->value;
        }

        return $settingsParam;
    }


}
