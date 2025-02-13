<?php
namespace App\Module\Front\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class SignPresenter extends Nette\Application\UI\Presenter
{
	protected function createComponentSignInForm(): Form
	{
		$form = new Form;
		$form->addText('username', 'Uživatelské jméno:')
			->setRequired('Prosím vyplňte své uživatelské jméno.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Prosím vyplňte své heslo.');

		$form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}


    public function signInFormSucceeded(Form $form): void
    {
       $data = $form->getValues();
       $username = isset($data->username) ? strval($data->username) : '';  // Pokud není setováno, použije prázdný řetězec
       $password = isset($data->password) ? strval($data->password) : '';

        try {
            $this->getUser()->login($username, $password);
            $this->redirect('Homepage:');
    
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Nesprávné přihlašovací jméno nebo heslo.');
        }
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.');
        $this->redirect('Homepage:');
    }


}
