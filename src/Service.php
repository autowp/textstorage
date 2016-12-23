<?php

namespace Autowp\TextStorage;

use Autowp\TextStorage\Exception;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class Service
{
    /**
     * @var Adapter
     */
    private $adapter = null;

    /**
     * @var TableGateway
     */
    private $textTable = null;

    /**
     * @var TableGateway
     */
    private $revisionTable = null;

    /**
     * @var string
     */
    private $textTableName = 'textstorage_text';

    /**
     * @var string
     */
    private $revisionTableName = 'textstorage_revision';

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return Service
     * @throws Exception
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (! method_exists($this, $method)) {
                $this->raise("Unexpected option '$key'");
            }

            $this->$method($value);
        }

        return $this;
    }

    /**
     * @param string $message
     * @throws Exception
     */
    private function raise($message)
    {
        throw new Exception($message);
    }

    /**
     * @param Adapter $dbAdapter
     * @return TextStorage
     */
    public function setDbAdapter(Adapter $dbAdapter)
    {
        $this->adapter = $dbAdapter;

        return $this;
    }

    /**
     * @param string $name
     * @return TextStorage
     */
    public function setTextTableName($name)
    {
        $this->textTableName = (string)$name;

        return $this;
    }

    /**
     * @param string $name
     * @return TextStorage
     */
    public function setRevisionTableName($name)
    {
        $this->revisionTableName = (string)$name;

        return $this;
    }

    /**
     * @return TableGateway
     */
    private function getTextTable()
    {
        if (null === $this->textTable) {
            $this->textTable = new TableGateway($this->textTableName, $this->adapter);
        }

        return $this->textTable;
    }

    /**
     * @return TableGateway
     */
    private function getRevisionTable()
    {
        if (null === $this->revisionTable) {
            $this->revisionTable = new TableGateway($this->revisionTableName, $this->adapter);
        }

        return $this->revisionTable;
    }

    public function getFirstText(array $ids)
    {
        if (! $ids) {
            return null;
        }
        $row = $this->getTextTable()->select(function (Select $select) use ($ids) {
            $select
                ->where([
                    'id' => $ids
                ])
                ->order(new Expression(
                    'FIELD(id, '.implode(', ', array_fill(0, count($ids), '?')).')',
                    $ids
                ))
                ->limit(1);
        })->current();

        if ($row) {
            return $row['text'];
        }

        return null;
    }

    private function getTextRow($id)
    {
        return $this->getTextTable()->select(function (Select $select) use ($id) {
            $select->where->equalTo('id', $id);
            $select->limit(1);
        })->current();
    }

    public function getText($id)
    {
        $row = $this->getTextRow($id);

        if ($row) {
            return $row->text;
        }

        return null;
    }

    public function getTextInfo($id)
    {
        $row = $this->getTextRow($id);

        if ($row) {
            return [
                'text'     => $row['text'],
                'revision' => $row['revision']
            ];
        }

        return null;
    }

    public function getRevisionInfo($id, $revision)
    {
        $row = $this->getRevisionTable()->select([
            'text_id'  => (int)$id,
            'revision' => (int)$revision
        ])->current();

        if ($row) {
            return [
                'text'     => $row['text'],
                'revision' => $row['revision'],
                'user_id'  => $row['user_id']
            ];
        }

        return null;
    }

    public function setText($id, $text, $userId)
    {
        $row = $this->getTextRow($id);

        if (! $row) {
            return $this->raise('Text `' . $id . '` not found');
        }

        if ($row['text'] != $text) {
            $this->getTextTable()->update([
                'revision'     => new Expression('revision + 1'),
                'text'         => $text,
                'last_updated' => new Expression('NOW()')
            ], [
                'id = ?' => $row['id']
            ]);

            $row = $this->getTextRow($row['id']);

            $this->getRevisionTable()->insert([
                'text_id'   => $row['id'],
                'revision'  => $row['revision'],
                'text'      => $row['text'],
                'timestamp' => $row['last_updated'],
                'user_id'   => $userId
            ]);
        }

        return $row->id;
    }

    public function createText($text, $userId)
    {
        $table = $this->getTextTable();

        $row = $table->insert([
            'revision'     => 0,
            'text'         => '',
            'last_updated' => new Expression('NOW()')
        ]);

        return $this->setText($table->getLastInsertValue(), $text, $userId);
    }

    public function getTextUserIds($id)
    {
        $table = $this->getRevisionTable();
        $db = $table->getAdapter();

        $rows = $table->select(function (Select $select) use ($id) {
            $select
                ->columns(['user_id'])
                ->quantifier(Select::QUANTIFIER_DISTINCT);
            $select->where->isNotNull('user_id');
            $select->where->equalTo('text_id', (int)$id);
        });

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row['user_id'];
        }

        return $ids;
    }
}
