<?php
namespace App\Module\Front\Presenters;

use Nette;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\User\UserFacade;

final class ShopPresenter extends BasePresenter {
    public function __construct(
        private UsersRepository $usersRepository,
        private UserFacade $userFacade
    ) {

    }
    public function renderCart(): void {
        $session = $this->getSession("cart");
        $this->template->duration = $session->duration;
        $this->template->premiumUntil = $session->premiumUntil->format('d-m-Y');
        $this->template->price = $session->price;

    }

    public function handleSubmitCart(): void {
        bdump("niga");
        $session = $this->getSession("cart");
        $user = $this->userFacade->getUserDTO(($this->getUser())->id);
        bdump((array)$user);
        $this->usersRepository->saveRow((array)$session->premiumUntil, $user->id);


    }

    public function renderPremium(): void
    {
        $duration = $this->getParameter('duration') ?? "1m";
        bdump($duration);
        $this->template->duration = $duration;

    }

    public function handleSubmitPremium($duration) {
        $session = $this->getSession("cart");
        bdump($duration);

        $premiumMap = [
            '1m' => ['+1 month', 49],
            '3m' => ['+3 months', 129],
            '6m' => ['+6 months', 219],
            '12m' => ['+12 months', 399],
        ];

        if (!isset($premiumMap[$duration])) {
            $this->flashMessage('Neplatná délka předplatného.', 'error');
            $this->redirect('this');
        }

        /*  zkrácená verze oproti:
            $modify = $map[$duration][0];
            $price = $map[$duration][1];
        */
        [$modify, $price] = $premiumMap[$duration];

        $session->duration = substr($modify, 1);
        $session->price = $price;
        $session->premiumUntil = (new \DateTimeImmutable())->modify($modify);

        bdump($session->premiumUntil);
        
        $this->flashMessage('Předplatné bylo přidáno do košíku.');
        $this->redirect('Shop:cart');
    }

}