<?php

declare(strict_types=1);


namespace App\Module\Front\Presenters;


use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Security\MyAuthorizator;
use Nette\Application\UI\Form;
use App\Module\Model\Settings\SettingsRepository;


final class HomepagePresenter extends BasePresenter
{
    public function __construct(
		private PostsRepository $postsRepository,
		private SettingsRepository $settingsRepository,
		private int $postsPerPage = 5
	) {
	}

	public function startup(): void
	{
		parent::startup();
		$this->postsPerPage = (int) ($this->settingsRepository->getRowByKey("postsPerPage"))->value;
		bdump($this->postsPerPage);
	}

    public function renderDefault(): void
    {	
		$numberOfPosts = $this->postsRepository->getNumberOfRows();	//získá počet všech záznamů z tabulku posts
		$this->template->postsArray = $this->postsRepository->getSomePostsFromEnd($this->postsPerPage, 0);	//vezme z konce tabulky (jeden tedy od nejnovější po nejstarší) "howMany" postů a přeskočí "from" postů
		$this->template->pages = $this->getNumberOfPages();

		//DEBUG
		bdump($this->getUserRole());
		bdump($numberOfPosts);
		bdump((int) $numberOfPosts/$this->postsPerPage + $this->restPage($numberOfPosts));
		bdump((int) $numberOfPosts/$this->postsPerPage);
		bdump((int) $numberOfPosts%$this->postsPerPage);
		bdump($this->restPage($numberOfPosts));
    }

	public function renderPage(int $page)
	{
		$this->template->postsArray = $this->postsRepository->getSomePostsFromEnd($this->postsPerPage, ($page-1)*$this->postsPerPage);
		bdump($page);
		$this->template->page =$page;
		$numberOfPosts = $this->postsRepository->getNumberOfRows();
		$this->template->pages = $this->getNumberOfPages();
		bdump((int) $numberOfPosts/$this->postsPerPage + $this->restPage($numberOfPosts));
	}

	public function restPage($numberOfPosts)	//metoda co přidá stránku kde jsou posty co zbydou po dělení
	{
		if ($numberOfPosts%$this->postsPerPage == 0) {
			return 0;
		}
		return 1;
	}

	public function actionSubmitForm()
	{
		$page = $this->getHttpRequest()->getPost('page');
		$this->renderPage((int) $page);
	}



	public function createComponentPageForm(): Form
	{
		$form = new Nette\Application\UI\Form;
    
    	$form->addText('page')
        	->setRequired('Toto pole je povinné.')
        	->setHtmlAttribute('type', 'number');
    	$form->addSubmit('submit', 'Přejít na stránku');

    	$form->onSuccess[] = [$this, 'pageFormSucceeded'];

    	return $form;
	}


	public function pageFormSucceeded(Form $form)
	{
		$data = $form->getValues();
		$page = $data->page;
		if ($page < 1 || $page > $this->getNumberOfPages()) {
			$form->addError("Zadejte platné číslo stránky");
		} else {
			$this->redirect("Homepage:page", (int) $page);
		}
		
	}

	public function getNumberOfPages(){
		return (int) ($this->postsRepository->getNumberOfRows()/$this->postsPerPage + $this->restPage($this->postsRepository->getNumberOfRows()));
	}




	

}
