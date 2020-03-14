<?php

declare(strict_types=1);

namespace Autowp\TextStorage;

use ArrayObject;
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
    private ?Adapter $adapter;

    private TableGateway $textTable;

    private TableGateway $revisionTable;

    private string $textTableName = 'textstorage_text';

    private string $revisionTableName = 'textstorage_revision';

    /**
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        $this->adapter = null;
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
        if (! isset($this->textTable)) {
            $this->textTable = new TableGateway($this->textTableName, $this->adapter);
        }

        return $this->textTable;
    }

    private function getRevisionTable(): TableGateway
    {
        if (! isset($this->revisionTable)) {
            $this->revisionTable = new TableGateway($this->revisionTableName, $this->adapter);
        }

        return $this->revisionTable;
    }

    public function getFirstText(array $ids): ?string
    {
        if (! $ids) {
            return null;
        }

        $select = $this->getTextTable()->getSql()->select()
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

        $row = $this->getTextTable()->selectWith($select)->current();

        if (! $row) {
            return null;
        }

        return $row['text'];
    }

    /**
     * @return array|ArrayObject|null
     */
    private function getTextRow(int $textID)
    {
        $select = $this->getTextTable()->getSql()->select()
            ->where(['id' => $textID])
            ->limit(1);

        return $this->getTextTable()->selectWith($select)->current();
    }

    public function getText(int $textID): ?string
    {
        $row = $this->getTextRow($textID);

        if (! $row) {
            return null;
        }

        return $row['text'];
    }

    public function getTextInfo(int $textID): ?array
    {
        $row = $this->getTextRow($textID);

        if ($row) {
            return [
                'text'     => $row['text'],
                'revision' => (int) $row['revision'],
            ];
        }

        return null;
    }

    public function getRevisionInfo(int $textID, int $revision): ?array
    {
        $row = $this->getRevisionTable()->select([
            'text_id'  => $textID,
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
    public function setText(int $textID, string $text, int $userID): int
    {
        $row = $this->getTextRow($textID);

        if (! $row) {
            throw new Exception(sprintf('Text `%d` not found', $textID));
        }

        if ($row['text'] !== $text) {
            $this->getTextTable()->update([
                'revision'     => new Expression('revision + 1'),
                'text'         => $text,
                'last_updated' => new Expression('NOW()'),
            ], [
                'id' => $row['id'],
            ]);

            $row = $this->getTextRow($textID);

            if (! $row) {
                throw new Exception(sprintf('Text `%d` not found', $textID));
            }

            $this->getRevisionTable()->insert([
                'text_id'   => $row['id'],
                'revision'  => $row['revision'],
                'text'      => $row['text'],
                'timestamp' => $row['last_updated'],
                'user_id'   => $userID,
            ]);
        }

        return (int) $row['id'];
    }

    /**
     * @throws Exception
     */
    public function createText(string $text, int $userID): int
    {
        $table = $this->getTextTable();

        $table->insert([
            'revision'     => 0,
            'text'         => '',
            'last_updated' => new Expression('NOW()'),
        ]);

        return $this->setText((int) $table->getLastInsertValue(), $text, $userID);
    }

    public function getTextUserIds(int $textID): array
    {
        $select = $this->getRevisionTable()->getSql()->select()
            ->columns(['user_id'])
            ->quantifier(Select::QUANTIFIER_DISTINCT)
            ->where([
                'user_id IS NOT NULL',
                'text_id' => $textID,
            ]);
        $rows   = $this->getRevisionTable()->selectWith($select);

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['user_id'];
        }

        return $ids;
    }
}
