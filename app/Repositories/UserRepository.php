<?php

declare(strict_types=1);

class UserRepository
{
    public function findByLoginId(string $loginId): ?array
    {
        $statement = db_connection()->prepare(
            'SELECT id, login_id, password_hash, role, name FROM users WHERE login_id = :login_id LIMIT 1'
        );
        $statement->execute(['login_id' => $loginId]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }
}
