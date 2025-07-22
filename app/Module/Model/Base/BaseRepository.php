<?php
namespace App\Module\Model\Base;

use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;


class BaseRepository
{
    protected string $table;
	public function __construct(
		protected Nette\Database\Explorer $database,
	) {
	}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getNumberOfRows()
    {
        return $this->database->table($this->table)->count();
    }

    public function getAll(): array//Selection
    {
        return $this->database
			->table($this->table)
			//->where('created_at < ', new \DateTime)
			//->order('created_at DESC');
            ->fetchAll();
    }

   

    public function getRowById(int $id): ActiveRow|null
    {
        $row = $this->database->table($this->table)->get($id);
        if ($row instanceof ActiveRow)
        {
            return $row;
        }
        return null;
    }

    
    //public function insertRow(array $data)
    //{
	//	return $this->database->table($this->table)->insert($data);
//	}

    public function deleteRow(int $id): int|null
    {
        bdump($this->table);
        $row = $this->getRowById($id);
        if($row instanceof ActiveRow)
        {
            return $row->delete();
        }
        return null;
	}

  //  public function updateRow(array $data, int $id): bool
  //  {
   //     return $this->getRowById($id)->update($data);
  //  }

    
    /**
     * Summary of saveRow
     * @param array<string, ?int> $data
     * @param ?int $id
     * @return Nette\Database\Table\ActiveRow|null
     */
    public function saveRow(array $data, ?int $id): ActiveRow|null
    {
        if (!$id || !$this->getRowById($id)){
            $row = $this->database->table($this->table)->insert($data);
            if($row instanceof ActiveRow) 
            {
                return $row;
            } 
            return null;
        } else{
            $this->getRowById($id)->update($data);
        }
        return null;
    }


}