<?php
namespace App\Module\Model\Post;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\Post\CommentDTO;

class CommentMapper {
    public function __construct(
    ) {}

    public static function map(ActiveRow $row): CommentDTO {
        return new CommentDTO($row->id, $row->post_id, $row->name, $row->email, $row->content);
    }
}