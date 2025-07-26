<?php

declare(strict_types=1);


namespace App\Module\Front\Presenters;


use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Security\MyAuthorizator;
use Nette\Application\UI\Form;
use App\Module\Model\Settings\SettingsRepository;
use App\Module\Model\User\UserFacade;
use App\Service\CurrentUserService;
use App\Module\Components\Paginator\PaginatorComponent;


final class HomepagePresenter extends BasePresenter
{
    public function __construct(
		private PostsRepository $postsRepository,
		private SettingsRepository $settingsRepository,
		private UserFacade $userFacade,
		private CurrentUserService $currentUser,
		private int $postsPerPage = 5
	) {
	}

	public function startup(): void
	{
		parent::startup();
		$this->postsPerPage = (int) ($this->settingsRepository->getRowByKey("postsPerPage"))->value;
		bdump($this->postsPerPage);
	}



	public function renderDefault()	//vezme číslo page, na kterou má skočit
	{
        $session = $this->getSession()->getSection('navigation');
		$page = intval($this->getParameter('page') ?? 1);
		bdump($page);
		$session->homepageUrl = $this->getHttpRequest()->getUrl()->getAbsoluteUrl();
		$this->template->postsArray = $this->postsRepository->getSomePostsFromEnd($this->postsPerPage, ($page-1)*$this->postsPerPage);	//vezme "postsPerPage" postů od pozice page na kterou skočit -1 bcs se jede od 0
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
        	->setHtmlAttribute('type', 'number')
			->setHtmlAttribute("class", "form-control");
    	$form->addSubmit('submit', 'Přejít na stránku')
			 ->setHtmlAttribute("class", "btn btn-outline-primary");

    	$form->onSuccess[] = [$this, 'pageFormSucceeded'];

    	return $form;
	}


	public function pageFormSucceeded(Form $form)
	{
		$data = $form->getValues();
		$page = $data->page;
		if ($page < 1 || $page > $this->getNumberOfPages()) {
			$this->flashMessage("Zadejte platné číslo stránky", "danger");
		} else {
			$this->redirect("Homepage:page", (int) $page);
		}
		
	}

	protected function createComponentPaginator(): PaginatorComponent
	{
    	$comp = new PaginatorComponent;
    	$comp->setCurrentPage(intval($this->getParameter('page') ?? 1));
	    $comp->setTotalPages($this->getNumberOfPages());

	    $comp->onPageChange[] = function (int $page): void {	//do toho pole se přidá funkce na redirect
	        $this->redirect('this', ['page' => $page]);
	    };

	    return $comp;
	}

	public function getNumberOfPages(){	//spočítá počet stránek na základě počtu postů v db a počtu postů co se můžou zobrazit na jedné stránce 
		return (int) ($this->postsRepository->getNumberOfRows()/$this->postsPerPage + $this->restPage($this->postsRepository->getNumberOfRows()));
	}




	

}
