<?php
namespace App\Module\Model\ExternalPost;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\ExternalPost\ExternalPostDTO;

class ExternalPostMapper {
    public function __construct() {}
    public function map (ActiveRow $row): ExternalPostDTO {
        return new ExternalPostDTO($row->id, $row->guid, $row->post_id);
    }
}