<?php
namespace rock\db;

use rock\base\BaseException;
use rock\base\ObjectTrait;
use rock\log\Log;
use rock\Rock;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling {@see \rock\db\Connection::beginTransaction()}.
 *
 * The following code is a typical example of using transactions (note that some
 * DBMS may not support transactions):
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch (Exception $e) {
 *     $transaction->rollBack();
 * }
 * ```
 *
 * @property boolean $isActive Whether this transaction is active. Only an active transaction can {@see \rock\db\Transaction::commit()}
 * or {@see \rock\db\Transaction::rollBack()}. This property is read-only.
 * @property string $isolationLevel The transaction isolation level to use for this transaction. This can be
 * one of {@see \rock\db\Transaction::READ_UNCOMMITTED}, {@see \rock\db\Transaction::READ_COMMITTED}, {@see \rock\db\Transaction::REPEATABLE_READ} and {@see \rock\db\Transaction::SERIALIZABLE} but also a string
 * containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is
 * write-only.
 */
class Transaction
{
    use ObjectTrait;
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const READ_COMMITTED = 'READ COMMITTED';
    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const REPEATABLE_READ = 'REPEATABLE READ';
    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * @var Connection the database connection that this transaction is associated with.
     */
    public $connection;
    /**
     * @var integer the nesting level of the transaction. 0 means the outermost level.
     */
    private $_level = 0;


    /**
     * Returns a value indicating whether this transaction is active.
     * @return boolean whether this transaction is active. Only an active transaction
     * can {@see \rock\db\Transaction::commit()} or {@see \rock\db\Transaction::rollBack()}.
     */
    public function getIsActive()
    {
        return $this->_level > 0 && $this->connection && $this->connection->isActive;
    }

    /**
     * Begins a transaction.
     *
     * @param string|null $isolationLevel The [isolation level][] to use for this transaction.
     * This can be one of {@see \rock\db\Transaction::READ_UNCOMMITTED}, {@see \rock\db\Transaction::READ_COMMITTED}, {@see \rock\db\Transaction::REPEATABLE_READ} and {@see \rock\db\Transaction::SERIALIZABLE} but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgreSQL, where setting the isolation level before the transaction
     * has no effect. You have to call {@see \rock\db\Transaction::setIsolationLevel()} in this case after the transaction has started.
     *
     * > Note: Some DBMS allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature
     * you may need to set the isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     * @throws DbException if {@see \rock\db\Transaction::$connection} is `null`.
     */
    public function begin($isolationLevel = null)
    {
        if ($this->connection === null) {
            throw new DbException('Transaction::db must be set.');
        }
        $this->connection->open();

        if ($this->_level == 0) {
            if ($isolationLevel !== null) {
                $this->connection->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }
            Rock::trace(
                'db',
                [
                    'msg' => 'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : ''),
                    'method' => __METHOD__
                ]
            );
            $this->connection->trigger(Connection::EVENT_BEGIN_TRANSACTION);
            $this->connection->pdo->beginTransaction();
            $this->_level = 1;

            return;
        }

        $schema = $this->connection->getSchema();
        if ($schema->supportsSavepoint()) {
            Rock::trace(
                'db',
                [
                    'msg' => 'Set savepoint ' . $this->_level,
                    'method' => __METHOD__
                ]
            );
            $schema->createSavepoint('LEVEL' . $this->_level);
        } else {
            if (class_exists('\rock\log\Log')) {
                $message = BaseException::convertExceptionToString(new DbException('Transaction not started: nested transaction not supported'));
                Log::info($message);
            }
        }
        $this->_level++;
    }

    /**
     * Commits a transaction.
     *
     * @throws DbException if the transaction is not active
     */
    public function commit()
    {
        if (!$this->getIsActive()) {
            throw new DbException('Failed to commit transaction: transaction was inactive.');
        }

        $this->_level--;
        if ($this->_level == 0) {
            Rock::trace(
                'db',
                [
                    'msg' => 'Commit transaction',
                    'method' => __METHOD__
                ]
            );
            $this->connection->pdo->commit();
            $this->connection->trigger(Connection::EVENT_COMMIT_TRANSACTION);
            return;
        }

        $schema = $this->connection->getSchema();
        if ($schema->supportsSavepoint()) {
            Rock::trace(
                'db',
                [
                    'msg' => 'Release savepoint ' . $this->_level,
                    'method' => __METHOD__
                ]
            );
            $schema->releaseSavepoint('LEVEL' . $this->_level);
        } else {
            if (class_exists('\rock\log\Log')) {
                $message = BaseException::convertExceptionToString(new DbException('Transaction not committed: nested transaction not supported'));
                Log::info($message);
            }
        }
    }

    /**
     * Rolls back a transaction.
     *
     * @throws DbException if the transaction is not active
     */
    public function rollBack()
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }

        $this->_level--;
        if ($this->_level == 0) {
            Rock::trace(
                'db',
                [
                    'msg' => 'Roll back transaction',
                    'method' => __METHOD__
                ]
            );
            $this->connection->pdo->rollBack();
            $this->connection->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);
            return;
        }

        $schema = $this->connection->getSchema();
        if ($schema->supportsSavepoint()) {
            Rock::trace(
                'db',
                [
                    'msg' => 'Roll back to savepoint ' . $this->_level,
                    'method' => __METHOD__
                ]
            );
            $schema->rollBackSavepoint('LEVEL' . $this->_level);
        } else {
            // throw an exception to fail the outer transaction
            throw new DbException('Roll back failed: nested transaction not supported.');
        }
    }

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However this is not supported by all DBMS so you might rather specify the isolation level directly
     * when calling {@see \rock\db\Transaction::begin()}.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of {@see \rock\db\Transaction::READ_UNCOMMITTED}, {@see \rock\db\Transaction::READ_COMMITTED}, {@see \rock\db\Transaction::REPEATABLE_READ} and {@see \rock\db\Transaction::SERIALIZABLE} but
     * also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * @throws DbException if the transaction is not active
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel($level)
    {
        if (!$this->getIsActive()) {
            throw new DbException('Failed to set isolation level: transaction was inactive.');
        }
        Rock::trace(
            'db',
            [
                'msg' => 'Setting transaction isolation level to ' . $level,
                'method' => __METHOD__
            ]
        );
        $this->connection->getSchema()->setTransactionIsolationLevel($level);
    }
}
