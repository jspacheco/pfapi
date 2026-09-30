<?php
namespace App\Models;
use CodeIgniter\Model;

class SeamanModel extends Model
{
    protected $table = 'pfaces.pfs_seaman_table';

    public function searchSeaman(string $surname = '', string $givenname = '', string $middlename = '', string $birthday = '', int $pagenum = 1): array
    {
        $builder = $this->db->table($this->table);
        $builder->select("id_no,
            last_name as surname,
            first_name as givenname,
            middle_init as middlename,
            date_of_birth as birthday,
            COALESCE(EXTRACT(YEAR FROM AGE(current_date,date_of_birth)), 0)::smallint age
        ");

        if ($surname !== '') {
            $builder->like('last_name', $surname, 'after');
        }

        if ($givenname !== '') {
            $builder->like('first_name', $givenname, 'after');
        }

        if ($middlename !== '') {
            $builder->like('middle_init', $middlename, 'after');
        }

        if ($birthday !== '') {
            $builder->where('date_of_birth', $birthday);
        }
        $pageSize = 20;
        $totalRows = $builder->countAllResults(false);
        $offset = ($pagenum - 1) * $pageSize;

        $result =  $builder
                    ->limit($pageSize, $offset)
                    ->get()
                    ->getResultArray();
        return ['page' => $pagenum
                ,'page_size' => $pageSize
                ,'total_rows' => $totalRows
                ,'total_pages' => ceil($totalRows/$pageSize)
                ,'a_data' => $result];
    } 
}