<?php
namespace App\Module\Model\Post;

use App\Module\Model\Post\PostMapper;
use Nette;
use App\Module\Model\Post\PostsRepository;
use App\Module\Model\Comment\CommentsRepository;
use App\Module\Model\Post\PostDTO;
use App\Module\Model\User\UsersRepository;
use App\Module\Model\Like\LikesRepository;

final class PostFacade  //facade je komplexnější práci s nějakym repository, prostě složitější akce, plus může pracovat s víc repos najednou
{
	public function __construct(
		private PostsRepository $postsRepository,
		private CommentsRepository $commentsRepository,
        protected Nette\Database\Explorer $database,
        private PostMapper $postMapper,
        private UsersRepository $usersRepository,
        private LikesRepository $likesRepository
	) {
	}

    public function filterPostColumns($data)
    {
        //funkce na filtraci dat z posts db na něco uživatelsky přívětívého
        foreach($data as $index => $line){
            $lineData = $line->toArray();
            foreach($lineData as $column => $value) {
                if ($column == "user_id") {
                    //$data[$column] = "Napsáno uživatelem: ";
                    //$data[$value] = ($this->usersRepository->getRowById($value))->username;
                    $lineData["Od uživatele: "] = ($this->usersRepository->getRowById($value))->username;
                }
                //bdump("$column, $value");
            }
            $data[$index] = $lineData;
        }         
        //bdump($data);
        return $data;
    }

    public function getPostsByFilter(string $column, string $parameter)
    {
        if ($column == "id" && $parameter) {
            return $this->database->table($this->postsRepository->getTable())->where($column, $parameter)->fetchAll();
        }

        if ($column == "user_id" && $parameter) //parameter je jméno a ne id, uživateli se totiž bude líp hledat podle jména a ne podle id
        {
            $user = $this->usersRepository->getRowByUsername($parameter); //takže podle jména najdu usera
            if ($user) {
                return $this->database->table($this->postsRepository->getTable())->where($column, $user->id)->fetchAll();   //a podle jeho id vyhledam record v db
            }
            return $this->database->table($this->postsRepository->getTable())->where($column, "")->fetchAll();  //vyhodí 0 záznamů pokud v se db nic nenašlo podle parametru
        }
        return $this->database->table($this->postsRepository->getTable())->where("{$column} LIKE ?", "%$parameter%")->fetchAll();   //i když dostane prázdnej string tak to vrátí všechno, protože LIKE vrací záznamy co obsahujou někde to cos zadal, proto u samotnáho WHERE to s "" vyhodí nic, protože se ptáš "vyhoď řádek co má v danym sloupci jenom hodnotu nic"
    }

    public function getNumberOfLikes(int $id)
    {
        return sizeof($this->likesRepository->getRowsByPostId($id));
    }

    public function deletePost(int $id): void
    {
        $this->database->transaction(function () use ($id) {
            $this->commentsRepository->deleteCommentByPostId($id);
            $this->postsRepository->deleteRow($id);
        });
    }

    public function getPostDTO(int $id): PostDTO    //jedna funkce co za tebe převede row na DTO aniž bys to v kodu musel vypisovat jak kokot 
    {
        $postRow = $this->postsRepository->getRowById($id);
        return $this->postMapper->map($postRow);
    }


}
