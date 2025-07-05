<?php
namespace App\Module\Model\ExternalPost;

use Nette;
use App\Module\Model\Base\BaseRepository;
use App\Module\Model\ExternalPost\ExternalPostMapper;

final class ExternalPostsRepository extends BaseRepository
{
    public function __construct(
        protected Nette\Database\Explorer $database,    //bez protected to hází error, proč?
        public ExternalPostMapper $externalPostMapper
    ) {
        $this->table = "external_posts";
    }

    public function getExternalPostByGuid($guid) {
        return $this->database->table($this->table)->where("guid", $guid)->fetch();
    }

    public function deleteExternalPostByPostId(int $id): void
    {
        $this->database->table($this->table)->where("post_id", $id)->delete();
    }
}