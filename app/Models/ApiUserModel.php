<?php
namespace App\Models;
use CodeIgniter\Model;

class ApiUserModel extends Model
{
    protected $table = 'pfapi.pfaces_api_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'username',
        'api_key',
        'active',
        'id'
    ];
}