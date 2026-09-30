<?php

namespace App\Controllers;

use App\Models\ApiLogModel;
use App\Models\SeamanModel;


class SearchController extends BaseApiController
{
    public function search()
    {
        // $jwtUser = service('request')->jwtUser; -- BaseAPIController handles this part
        $username = $this->jwtUser->username;
        $data = $this->request->getJSON(true);

        $sname = $data['surname'] ?? '';
        $gname = $data['givenname'] ?? '';
        $mname = $data['middlename'] ?? '';

        if (strlen($sname.$gname.$mname) > 0)
        {
            $model = new SeamanModel();
            $result = $model->searchSeaman(
                $sname,
                $gname,
                $mname,
                $data['birthday'] ?? '',
                $data['pagenum'] ?? 1
            );

            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/search',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => count($result)
            ]);

            return $this->respond([
                'status' => 'success',
                'message' => '',
                'a_data' => $result
            ]);
        }
        else
        {
            $logModel = new ApiLogModel();
            $logModel->insert([
                'username' => $username,
                'endpoint' => '/search',
                'ip_address' => $this->request->getIPAddress(),
                'request_data' => json_encode($data),
                'response_count' => 0
            ]);

            return $this->respond([
                'status' => 'error',
                'message' => 'Missing parameters',
                'a_data' => []
            ]);
        }
    }
}