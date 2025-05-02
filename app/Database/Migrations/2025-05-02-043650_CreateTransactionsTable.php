<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'auto_increment' => true],
            'user_id_from'            => ['type' => 'INT', 'null' => true],
            'user_id_to'              => ['type' => 'INT'],
            'type'                    => ['type' => 'ENUM', 'constraint' => ['deposit', 'transfer']],
            'amount'                  => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'status'                  => ['type' => 'ENUM', 'constraint' => ['completed', 'reversed'], 'default' => 'completed'],
            'reversed_transaction_id' => ['type' => 'INT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
    }
}
