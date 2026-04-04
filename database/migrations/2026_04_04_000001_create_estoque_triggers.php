<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateEstoqueTriggers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Trigger para prevenir quantidades negativas e atualizar status automaticamente
        DB::unprepared('
            CREATE TRIGGER before_update_estoque
            BEFORE UPDATE ON estoque
            FOR EACH ROW
            BEGIN
                -- Prevenir quantidade negativa
                IF NEW.quantidade_atual < 0 THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Quantidade de estoque não pode ser negativa";
                END IF;
                
                -- Atualizar status_disponibilidade automaticamente
                IF NEW.quantidade_atual > 0 THEN
                    SET NEW.status_disponibilidade = "D";
                ELSE
                    SET NEW.status_disponibilidade = "I";
                END IF;
                
                -- Atualizar timestamp
                SET NEW.updated_at = NOW();
            END
        ');

        // Trigger para validação em INSERT
        DB::unprepared('
            CREATE TRIGGER before_insert_estoque
            BEFORE INSERT ON estoque
            FOR EACH ROW
            BEGIN
                -- Prevenir quantidade negativa
                IF NEW.quantidade_atual < 0 THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Quantidade de estoque não pode ser negativa";
                END IF;
                
                -- Atualizar status_disponibilidade automaticamente
                IF NEW.quantidade_atual > 0 THEN
                    SET NEW.status_disponibilidade = "D";
                ELSE
                    SET NEW.status_disponibilidade = "I";
                END IF;
            END
        ');

        // Criar tabela de auditoria de estoque
        DB::statement('
            CREATE TABLE IF NOT EXISTS estoque_auditoria (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                estoque_id BIGINT UNSIGNED NOT NULL,
                produto_id BIGINT UNSIGNED NOT NULL,
                unidade_id BIGINT UNSIGNED NOT NULL,
                quantidade_anterior INT NOT NULL,
                quantidade_nova INT NOT NULL,
                diferenca INT NOT NULL,
                operacao VARCHAR(20) NOT NULL COMMENT "INSERT, UPDATE, DELETE",
                usuario VARCHAR(255) NULL,
                data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_estoque_id (estoque_id),
                INDEX idx_produto_id (produto_id),
                INDEX idx_unidade_id (unidade_id),
                INDEX idx_data_hora (data_hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        // Trigger para auditoria em UPDATE
        DB::unprepared('
            CREATE TRIGGER after_update_estoque
            AFTER UPDATE ON estoque
            FOR EACH ROW
            BEGIN
                IF OLD.quantidade_atual <> NEW.quantidade_atual THEN
                    INSERT INTO estoque_auditoria (
                        estoque_id,
                        produto_id,
                        unidade_id,
                        quantidade_anterior,
                        quantidade_nova,
                        diferenca,
                        operacao,
                        usuario
                    ) VALUES (
                        NEW.id,
                        NEW.produto_id,
                        NEW.unidade_id,
                        OLD.quantidade_atual,
                        NEW.quantidade_atual,
                        NEW.quantidade_atual - OLD.quantidade_atual,
                        "UPDATE",
                        USER()
                    );
                END IF;
            END
        ');

        // Trigger para auditoria em INSERT
        DB::unprepared('
            CREATE TRIGGER after_insert_estoque
            AFTER INSERT ON estoque
            FOR EACH ROW
            BEGIN
                INSERT INTO estoque_auditoria (
                    estoque_id,
                    produto_id,
                    unidade_id,
                    quantidade_anterior,
                    quantidade_nova,
                    diferenca,
                    operacao,
                    usuario
                ) VALUES (
                    NEW.id,
                    NEW.produto_id,
                    NEW.unidade_id,
                    0,
                    NEW.quantidade_atual,
                    NEW.quantidade_atual,
                    "INSERT",
                    USER()
                );
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS before_update_estoque');
        DB::unprepared('DROP TRIGGER IF EXISTS before_insert_estoque');
        DB::unprepared('DROP TRIGGER IF EXISTS after_update_estoque');
        DB::unprepared('DROP TRIGGER IF EXISTS after_insert_estoque');
        DB::statement('DROP TABLE IF EXISTS estoque_auditoria');
    }
}
