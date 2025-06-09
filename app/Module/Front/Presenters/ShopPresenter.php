<?php
namespace App\Module\Front\Presenters;

use App\Module\Model\PremiumPurchase\PremiumPurchaseDTO;
use Nette;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\User\UserFacade;
use App\Module\Model\PremiumPurchase\PremiumPurchaseRepository;

final class ShopPresenter extends BasePresenter {
    public function __construct(
        private UsersRepository $usersRepository,
        private UserFacade $userFacade,
        private PremiumPurchaseRepository $premiumPurchaseRepository
    ) {

    }
    public function renderCart(): void {
        $session = $this->getSession("cart");
        $this->template->duration = $session->duration;
        $this->template->premiumUntil = $session->premiumUntil->format('d-m-Y');
        $this->template->price = $session->price;

    }

    public function handleSubmitCart(): void {
        $session = $this->getSession("cart");
        //$user = $this->userFacade->getUserDTO(($this->getUser())->id);
        //bdump((array)$user);
        $data = (array)$session->premiumUntil;
        $data['premium_until'] = $data['date'];
        unset($data['date'], $data['timezone_type'], $data['timezone']);

        $this->usersRepository->saveRow($data, ($this->getUser())->id);
        $this->premiumPurchaseRepository->saveRow($this->formatPremiumPurchseData($session, $this->getUser()->id), null);

        $this->flashMessage('Nákup proběhl úspěšně.');
        $this->redirect('Homepage:');
    }

    private function formatPremiumPurchseData($session, $userId)
    {
        $data = [];
        $data["user_id"] = $userId;
        $data["length"] = $session->duration;
        $data["price"] = $session->price;

        return $data;
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
        $user = $this->userFacade->getUserDTO(($this->getUser())->id);
        bdump((array)$user);


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
        if ($user->premiumUntil) {
            $session->premiumUntil = ($user->premiumUntil)->modify($modify);
        } else {
            $session->premiumUntil = (new \DateTimeImmutable())->modify($modify);
        }

        bdump($session->premiumUntil);
        
        $this->flashMessage('Předplatné bylo přidáno do košíku.');
        $this->redirect('Shop:cart');
    }

}