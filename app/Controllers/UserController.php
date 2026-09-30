<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $model = new UserModel();
        return $this->respond(
            $model->findAll()
        );
    }

    public function show($id = null)
    {
        $model = new UserModel();

        $user = $model->find($id);

        if (!$user) {
            return $this->failNotFound('User not found');
        }

        return $this->respond($user);
    }

    public function create()
    {
        $model = new UserModel();

        $data = $this->request->getJSON(true);

        $id = $model->insert($data);

        return $this->respondCreated([
            'id' => $id
        ]);
    }
}