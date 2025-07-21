<?php
namespace App\Module\Components\Paginator;

use Nette\Application\UI\Control;

class PaginatorComponent extends Control
{
    public int $currentPage = 1;
    public int $totalPages = 1;

    /** @var callable[] */
    public array $onPageChange = [];

    public function setCurrentPage(int $page): void
    {
        bdump("setCurrentPage: " . $page);
        $this->currentPage = $page;
    }

    public function setTotalPages(int $pages): void
    {
        $this->totalPages = $pages;
    }

    public function handleChangePage(int $page): void
    {
        if ($page >= 1 && $page <= $this->totalPages) {
            $this->onPageChange($page); //zavolá se funkce na redirect s parametrem stránky na přesměrování
        }
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/PaginatorComponent.latte');
        $this->template->currentPage = $this->currentPage;
        $this->template->totalPages = $this->totalPages;
        $this->template->render();
    }
}

