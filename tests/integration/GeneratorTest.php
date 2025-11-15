<?php

namespace ModelGenerator\Tests\Integration;

use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use ModelGenerator\Config\MConfig;
use ModelGenerator\Generator;

use ModelGenerator\Processor\CustomPrimaryKeyProcessor;
use ModelGenerator\Processor\CustomPropertyProcessor;
use ModelGenerator\Processor\FieldProcessor;
use ModelGenerator\Processor\NamespaceProcessor;
use ModelGenerator\Processor\RelationProcessor;
use ModelGenerator\Processor\TableNameProcessor;
use ModelGenerator\Illuminate\MDbManager;
use ModelGenerator\TypeRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    private static SQLiteConnection $connection;
    private Generator $generator;

    public static function setUpBeforeClass(): void
    {
        $connector = new SQLiteConnector();
        $pdo = $connector->connect([
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ]);
        self::$connection = new SQLiteConnection($pdo);

        $queries = explode("\n\n", file_get_contents(__DIR__ . '/resources/schema.sql'));
        foreach ($queries as $query) {
            self::$connection->statement($query);
        }
    }

    protected function setUp(): void
    {
        $getDatabaseManager = function (): DatabaseManager|MockObject {
            $databaseManagerMock = $this->createMock(DatabaseManager::class);
            $databaseManagerMock->expects($this->any())
                ->method('connection')
                ->willReturn(self::$connection);
                return $databaseManagerMock;
        };
        $databaseManagerMock =  new MDbManager($getDatabaseManager());

        $typeRegistry = new TypeRegistry($databaseManagerMock);

        $this->generator = new Generator([
            new CustomPrimaryKeyProcessor($databaseManagerMock, $typeRegistry),
            new CustomPropertyProcessor(),
            new FieldProcessor($databaseManagerMock, $typeRegistry),
            new NamespaceProcessor(),
            new RelationProcessor($databaseManagerMock),
            new TableNameProcessor($databaseManagerMock),
        ]);
    }

    /**
     * @dataProvider modelNameProvider
     */
    public function testGeneratedModel(string $modelName): void
    {
        $config = (new MConfig())
            ->setClassName($modelName)
            ->setNamespace('App\Models')
            ->setBaseClassName(Model::class);

        $model = $this->generator->generateModel($config);
        $this->assertEquals(file_get_contents(__DIR__ . '/resources/' . $modelName . '.php.generated'), $model->render());
    }

    public function modelNameProvider(): array
    {
        return [
            [
                'modelName' => 'User',
            ],
            [
                'modelName' => 'Role',
            ],
            [
                'modelName' => 'Organization',
            ],
            [
                'modelName' => 'Avatar',
            ],
            [
                'modelName' => 'Post',
            ],
        ];
    }

    public function testGeneratedModelWithCustomProperties(): void
    {
        $config = (new MConfig())
            ->setClassName('User')
            ->setNamespace('App')
            ->setBaseClassName('Base\ClassName')
            ->setNoTimestamps(true)
            ->setDateFormat('d/m/y');

        $model = $this->generator->generateModel($config);
        $this->assertEquals(file_get_contents(__DIR__ . '/resources/User-with-params.php.generated'), $model->render());
    }
}
