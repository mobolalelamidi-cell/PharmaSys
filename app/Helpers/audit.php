<?php

function audit_log(string $action, string $tableName, ?int $recordId = null): void
{
    $user = current_user();

    if ($user === null) {
        return;
    }

    $statement = Database::connection()->prepare(
        'INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (:user_id, :action, :table_name, :record_id)'
    );
    $statement->execute([
        'user_id' => $user['id'],
        'action' => $action,
        'table_name' => $tableName,
        'record_id' => $recordId,
    ]);
}
