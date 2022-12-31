<?php

namespace MrMadClown\Mnemosyne\Tests;

use MrMadClown\Mnemosyne\BaseRepository;
use MrMadClown\Mnemosyne\Model;

class BaseRepositoryTest extends BaseTest
{
    public function testDestroy()
    {
        $pdo = $this->mockPDO();
        $pdo->expects(static::once())
            ->method('prepare')
            ->with('DELETE FROM users WHERE id = ?;')
            ->willReturn($this->mockStatement());
        $model = new class implements Model {

            public static function getTableName(): string
            {
                return 'users';
            }

            public function exists(): bool
            {
                return false;
            }
        };
        $repository = new class($pdo, $model) extends BaseRepository {
            public function retrieve(int|string $primary): Model
            {
            }

            public function persist(Model $model): Model
            {
            }
        };

        $repository->destroy(1);
    }
}
