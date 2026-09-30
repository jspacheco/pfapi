<?php
namespace App\Models;
use CodeIgniter\Model;

class ApiLogModel extends Model
{
    protected $table = 'pfapi.pfaces_api_logs';
    protected $primaryKey = 'log_id';
    protected $allowedFields = [
        'username',
        'endpoint',
        'ip_address',
        'request_data',
        'response_count'
    ];
}