<?php
namespace App\Module\Model\Comment;

use Nette;
use Nette\Database\Table\ActiveRow;
use App\Module\Model\Comment\CommentDTO;

class CommentMapper {   //třída převádějíc Activerow comment (comment z metody getRowById z commentsRepository) na CommentDTO (Objekt commentu)
    public function __construct(
    ) {}

    public static function map(ActiveRow $row): CommentDTO {
        return new CommentDTO($row->id, $row->post_id, $row->name, $row->email, $row->content, $row->ownerUser_id, $row->replyTo);
    }
}