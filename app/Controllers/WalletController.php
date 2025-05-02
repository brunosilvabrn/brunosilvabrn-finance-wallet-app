<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\UserModel;
use App\Services\WalletService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class WalletController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $transactionModel; 
    protected $walletService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->transactionModel   = new TransactionModel();
        $this->walletService =    new WalletService();
    }

    public function deposit()
    {
        $userId = service('request')->user->id; 

        $data = $this->request->getJSON(true); 

        if (empty($data)) {
            $data = (array) $this->request->getJSON(true);
        }

        $amount = (float) $data['amount'];

        try {
            $tx = $this->walletService->deposit($userId, $amount);
            return $this->response->setJSON([
                'status'      => 'success',
                'transaction' => $tx,
                'balance'     => $tx['balance'],
            ]);
        } catch (\Exception $e) {
            return $this->response
                        ->setStatusCode(400)
                        ->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function transfer()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $fromId = service('request')->user->id;
        $toTransfer   = ($data['to_user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        
        $recipient = (new \App\Models\UserModel())
                        ->where('email', $toTransfer)
                        ->first();

        if (!$recipient) {
            return $this->respond([
                'status'      => 'error',
                'message' => 'Destinatário não encontrado para o e-mail informado.',
            ], 500);
            return $this->failNotFound('Destinatário não encontrado para o e-mail informado.', 404);
        }

        $toId = (int) $recipient['id'];
        

        try {
            $tx = $this->walletService->transfer($fromId, $toId, $amount);

            // Retornar também o saldo atualizado
            $user = (new \App\Models\UserModel())->find($fromId);
            return $this->respond([
                'status'      => 'success',
                'transaction' => $tx,
                'balance'     => (float) $user['balance'],
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 400);
        }
    }

    public function reverse($transactioID)
    {
        try {
            $revTx = $this->walletService->reverseTransaction((int) $transactioID);
            return $this->response->setJSON([
                'status'      => 'success',
                'balance'     => $revTx['balance'],
                'transaction'    => $revTx,
            ]);
        } catch (\Exception $e) {
            return $this->response
                        ->setStatusCode(400)
                        ->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function dataUserDashboard()
    {
        $jwtData = service('request')->user;
        $userId  = $jwtData->id;

        $userId = service('request')->user->id;

        try {
            $data = $this->walletService->getDashboardData($userId);
            return $this->respond($data);
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600
                    ? $e->getCode()
                    : 500;
            return $this->fail($e->getMessage(), $code);
        }
    }

    public function profile()
    {
        $jwtData = service('request')->user;  
        $userId  = $jwtData->id;

        $user = $this->userModel->find($userId);

        return $this->respond([
            'id'      => $user['id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'balance' => $user['balance'],
        ]);
    }
}
