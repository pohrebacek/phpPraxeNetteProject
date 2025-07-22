<?php
namespace App\Module\Front\Presenters;

use Nette;
use App\Module\Model\User\UserFacade;
use App\Module\Model\User\UsersRepository;
use Nette\Application\UI\Form;

final class UserPresenter extends BasePresenter
{
    public function __construct(
        private UserFacade $userFacade,
        private UsersRepository $usersRepository,
        protected Nette\Security\Passwords $passwords,
    ) {}
    public function renderShow(): void
    {
        $user = $this->userFacade->getUserDTO(($this->getUser())->id);
        $this->template->userInfo = $user;
    }

    public function renderEdit()
    {
        $user = $this->userFacade->getUserDTO(($this->getUser())->id);
        $this->getComponent("editUserForm")->setDefaults($user);

    }

    public function renderChangePassword()
    {

    }


    public function createComponentChangePasswordForm(): Form
    {
        $form = new Form;
        $form->addPassword("password", "Nové Heslo:")
             ->setRequired("Prosím zvolte si své nové heslo")
             ->setHtmlAttribute("class", "form-control");
        $form->addPassword("passwordCheck", "Zadejde své nové heslo znovu:")
             ->setRequired("Zadejde své nové heslo znovu:")
             ->setHtmlAttribute("class", "form-control");
        $form->addSubmit("send", "Změnit heslo")
             ->setHtmlAttribute("class", "btn btn-primary");
        
        $form->onSuccess[] = [$this, "changePasswordFormSucceeded"];
        return $form;
    }

    public function changePasswordFormSucceeded(Form $form)
    {
        $userId = ($this->getUser())->id;
        $data = $form->getValues();
        $data->password = $this->passwords->hash($data->password);
        if (!$this->passwords->verify($data->passwordCheck, $data->password)) {
            $this->flashMessage("Vámi zadaná hesla musí být stejná", "danger");
        } else {
            unset($data->passwordCheck);
            $this->usersRepository->saveRow((array) $data, $userId);
            $this->flashMessage("Vaše heslo bylo úspěšně změněno!");
        }
    }


    public function createComponentEditUserForm(): Form
    {
        $form = new Form;
        $form->addText("username", "Uživatelské jméno:")
             ->setRequired("Prosím, vyplňte uživatelské jméno")
             ->setHtmlAttribute("class", "form-control");
        $form->addEmail("email", "Emailová adresa:")
             ->setRequired("Prosim, vyplňte svou emailovou adresu")
             ->setHtmlAttribute("class", "form-control");
        $form->addSubmit("send", "Uložit")
             ->setHtmlAttribute("class", "btn btn-primary");
        $form->addButton("changePassword", "Změnit heslo")
             ->setHtmlAttribute("class", "btn btn-outline-primary")
             ->setHtmlAttribute("onclick", "window.location.href='" . $this->link('User:changePassword') . "'");
        
        
        $form->onSuccess[] = [$this, "editUserFormSucceeded"];
        return $form;
    } 


    public function editUserFormSucceeded(Form $form)
    {
        $data = $form->getValues();
        unset($data->changePassword);
        $foundUserByName = $this->usersRepository->getRowByUsername($data->username);
        $foundUserByEmail = $this->usersRepository->getRowByEmail($data->email);
        $userId = ($this->getUser())->id;

        if (($foundUserByName && $foundUserByName->id != $userId) || ($foundUserByEmail && $foundUserByEmail->id != $userId))
        {
            $this->flashMessages("Účet s těmito údaji již existuje", "danger");
        } else {
            $this->usersRepository->saveRow((array) $data, $userId);
            $this->flashMessage("Údaje byly úspěšně změněny");
        }

    }
}