<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\TransactionModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class WalletService
{
    protected $userModel;
    protected $txModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->txModel   = new TransactionModel();
        $this->db        = \Config\Database::connect();
    }

    /**
     * Retorna dados para o dashboard do usuário, com lista de transações.
     *
     * @param int $userId
     * @return array
     * @throws Exception Se usuário não for encontrado.
     */
    public function getDashboardData(int $userId): array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('Usuário não encontrado', 404);
        }
        $txs = $this->txModel
                    ->where('user_id_from', $userId)
                    ->orWhere('user_id_to', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();

        $list = array_map(function($tx) use ($userId) {
            if ($tx['type'] === 'deposit') {
                $type   = 'deposit';
                $amount = (float) $tx['amount'];
            } elseif ($tx['user_id_from'] == $userId) {
                $type   = 'send';
                $amount = - (float) $tx['amount'];
            } else {
                $type   = 'receive';
                $amount = (float) $tx['amount'];
            }

            if ($tx['type'] === 'deposit') {
                $description = 'Depósito';
            } elseif ($type === 'send') {
                $description = 'Enviado para usuário ' . $tx['user_id_to'];
            } else {
                $description = 'Recebido de usuário ' . $tx['user_id_from'];
            }

            return [
                'id'          => (int)   $tx['id'],
                'type'        => $type,
                'amount'      => $amount,
                'date'        => date('Y-m-d', strtotime($tx['created_at'])),
                'description' => $description,
                'reversed'    => $tx['status'] === 'reversed',
            ];
        }, $txs);

        return [
            'name'         => $user['name'],
            'balance'      => (float) $user['balance'],
            'transactions' => $list,
        ];
    }

    /**
     * Faz um depósito na conta do usuário.
     *
     * @param int   $userId ID do usuário que vai receber o depósito.
     * @param float $amount Valor a ser depositado (deve ser > 0).
     *
     * @return array Dados da transação criada.
     * @throws Exception Se usuário não existir, valor inválido ou erro de BD.
     */
    public function deposit(int $userId, float $amount): array
    {
        if ($amount <= 0) {
            throw new Exception('O valor do depósito deve ser maior que zero.');
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            throw new Exception('Usuário não encontrado.');
        }

        $this->db->transStart();

        try {

            $newBalance = (float) $user['balance'] + $amount;
            $this->userModel->update($userId, ['balance' => $newBalance]);

            $txData = [
                'user_id_from' => null,
                'user_id_to'   => $userId,
                'type'         => 'deposit',
                'balance'      => $newBalance,
                'amount'       => $amount,
                'status'       => 'completed',
                'created_at'   => date('Y-m-d H:i:s'),
            ];
            $this->txModel->insert($txData);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Falha ao registrar depósito no banco.');
            }

            $txId = $this->txModel->getInsertID();
            return array_merge(['id' => $txId], $txData);

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new Exception('Erro ao processar depósito: ' . $e->getMessage());
        }
    }

    public function reverseTransaction(int $txId): array
    {
        $orig = $this->txModel->find($txId);
        if (!$orig) {
            throw new Exception('Transação não encontrada.');
        }
        if ($orig['status'] === 'reversed') {
            throw new Exception('Transação já foi revertida.');
        }

        $this->db->transStart();
        try {
            if ($orig['type'] === 'deposit') {
                $toUser = $this->userModel->find($orig['user_id_to']);
                $newBalanceTo = $toUser['balance'] - $orig['amount'];
                $this->userModel->update($orig['user_id_to'], ['balance' => $newBalanceTo]);
            } else {
           
                $fromUser = $this->userModel->find($orig['user_id_from']);
                $toUser   = $this->userModel->find($orig['user_id_to']);

                $this->userModel->update($orig['user_id_from'], [
                    'balance' => $fromUser['balance'] + $orig['amount']
                ]);
                $this->userModel->update($orig['user_id_to'], [
                    'balance' => $toUser['balance'] - $orig['amount']
                ]);
            }

            $this->txModel->update($txId, ['status' => 'reversed']);

            $revData = [
                'user_id_from'          => $orig['type'] === 'transfer' ? $orig['user_id_to'] : null,
                'user_id_to'            => $orig['type'] === 'transfer' ? $orig['user_id_from'] : $orig['user_id_to'],
                'type'                  => $orig['type'],
                'amount'                => $orig['amount'],
                'balance'               => $toUser['balance'] - $orig['amount'],
                'status'                => 'completed',
                'reversed_transaction_id'=> $txId,
                'created_at'            => date('Y-m-d H:i:s'),
            ];
            $this->txModel->insert($revData);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Falha ao processar reversão no banco.');
            }

            $revId = $this->txModel->getInsertID();
            return array_merge(['id' => $revId], $revData);

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new Exception('Erro ao reverter transação: ' . $e->getMessage());
        }
    }

    /**
     * Envia valor de um usuário para outro.
     *
     * @param int   $fromId ID do remetente.
     * @param int   $toId   ID do destinatário.
     * @param float $amount Valor a ser enviado (> 0).
     * @return array Dados da transação.
     * @throws Exception Se inválido, sem saldo ou erro no BD.
     */
    public function transfer(int $fromId, int $toId, float $amount): array
    {
        if ($amount <= 0) {
            throw new Exception('O valor da transferência deve ser maior que zero.');
        }

        if ($fromId === $toId) {
            throw new Exception('Não é possível transferir para o mesmo usuário.');
        }

        $fromUser = $this->userModel->find($fromId);
        $toUser   = $this->userModel->find($toId);
        if (!$fromUser || !$toUser) {
            throw new Exception('Remetente ou destinatário não encontrado.');
        }

        if ($fromUser['balance'] < $amount) {
            throw new Exception('Saldo insuficiente para transferência.');
        }

        $this->db->transStart();
        try {
            $newFromBalance = $fromUser['balance'] - $amount;
            $this->userModel->update($fromId, ['balance' => $newFromBalance]);

            $newToBalance = $toUser['balance'] + $amount;
            $this->userModel->update($toId, ['balance' => $newToBalance]);

            $txData = [
                'user_id_from' => $fromId,
                'user_id_to'   => $toId,
                'type'         => 'transfer',
                'amount'       => $amount,
                'status'       => 'completed',
                'created_at'   => date('Y-m-d H:i:s'),
            ];
            $this->txModel->insert($txData);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new DatabaseException('Falha ao registrar transferência no banco.');
            }

            $txId = $this->txModel->getInsertID();
            return array_merge(['id' => $txId], $txData);

        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new Exception('Erro ao processar transferência: ' . $e->getMessage());
        }
    }
}
