<?php

declare(strict_types=1);

namespace Autowp\TextStorage;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;

use function array_fill;
use function count;
use function implode;
use function method_exists;
use function sprintf;
use function ucfirst;

class Service
{
    /** @var Adapter */
    private ?Adapter $adapter;

    /** @var TableGateway */
    private ?TableGateway $textTable;

    /** @var TableGateway */
    private ?TableGateway $revisionTable;

    /** @var string */
    private string $textTableName = 'textstorage_text';

    /** @var string */
    private string $revisionTableName = 'textstorage_revision';

    /**
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        $this->adapter       = null;
        $this->textTable     = null;
        $this->revisionTable = null;
        $this->setOptions($options);
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (! method_exists($this, $method)) {
                throw new Exception("Unexpected option '$key'");
            }

            $this->$method($value);
        }

        return $this;
    }

    public function setDbAdapter(Adapter $dbAdapter): self
    {
        $this->adapter = $dbAdapter;

        return $this;
    }

    public function setTextTableName(string $name): self
    {
        $this->textTableName = $name;

        return $this;
    }

    public function setRevisionTableName(string $name): self
    {
        $this->revisionTableName = $name;

        return $this;
    }

    private function getTextTable(): TableGateway
    {
        if (null === $this->textTable) {
            $this->textTable = new TableGateway($this->textTableName, $this->adapter);
        }

        return $this->textTable;
    }

    private function getRevisionTable(): TableGateway
    {
        if (null === $this->revisionTable) {
            $this->revisionTable = new TableGateway($this->revisionTableName, $this->adapter);
        }

        return $this->revisionTable;
    }

    public function getFirstText(array $ids): ?string
    {
        if (! $ids) {
            return null;
        }
        $row = $this->getTextTable()->select(function (Select $select) use ($ids) {
            $select
                ->columns(['text'])
                ->where([
                    'id' => $ids,
                    'length(text) > 0',
                ])
                ->order(new Expression(
                    'FIELD(id, ' . implode(', ', array_fill(0, count($ids), '?')) . ')',
                    $ids
                ))
                ->limit(1);
        })->current();

        if ($row) {
            return $row['text'];
        }

        return null;
    }

    /**
     * @return object|null
     */
    private function getTextRow(int $id)
    {
        return $this->getTextTable()->select(function (Select $select) use ($id) {
            $select->where->equalTo('id', $id);
            $select->limit(1);
        })->current();
    }

    public function getText(int $id): ?string
    {
        $row = $this->getTextRow($id);

        if ($row) {
            return $row->text;
        }

        return null;
    }

    public function getTextInfo(int $id): ?array
    {
        $row = $this->getTextRow($id);

        if ($row) {
            return [
                'text'     => $row['text'],
                'revision' => $row['revision'],
            ];
        }

        return null;
    }

    public function getRevisionInfo(int $id, int $revision): ?array
    {
        $row = $this->getRevisionTable()->select([
            'text_id'  => $id,
            'revision' => $revision,
        ])->current();

        if ($row) {
            return [
                'text'     => $row['text'],
                'revision' => $row['revision'],
                'user_id'  => $row['user_id'],
            ];
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function setText(int $id, string $text, int $userId): int
    {
        $row = $this->getTextRow($id);

        if (! $row) {
            throw new Exception(sprintf('Text `%s` not found', $id));
        }

        if ($row['text'] !== $text) {
            $this->getTextTable()->update([
                'revision'     => new Expression('revision + 1'),
                'text'         => $text,
                'last_updated' => new Expression('NOW()'),
            ], [
                'id' => $row['id'],
            ]);

            $row = $this->getTextRow($id);

            $this->getRevisionTable()->insert([
                'text_id'   => $row['id'],
                'revision'  => $row['revision'],
                'text'      => $row['text'],
                'timestamp' => $row['last_updated'],
                'user_id'   => $userId,
            ]);
        }

        return (int) $row['id'];
    }

    /**
     * @throws Exception
     */
    public function createText(string $text, int $userId): int
    {
        $table = $this->getTextTable();

        $table->insert([
            'revision'     => 0,
            'text'         => '',
            'last_updated' => new Expression('NOW()'),
        ]);

        return $this->setText((int) $table->getLastInsertValue(), $text, $userId);
    }

    public function getTextUserIds(int $id): array
    {
        $rows = $this->getRevisionTable()->select(function (Select $select) use ($id) {
            $select
                ->columns(['user_id'])
                ->quantifier(Select::QUANTIFIER_DISTINCT);
            $select->where->isNotNull('user_id');
            $select->where->equalTo('text_id', $id);
        });

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['user_id'];
        }

        return $ids;
    }
}
